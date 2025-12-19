<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewVoucherRequest;
use App\Http\Requests\AdminUpdateVoucherRequest;
use App\Http\Requests\VoucherSearchRequest;
use App\Sponsor;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Log;
use Throwable;

class VouchersController extends Controller
{
    /**
     * Display a listing of Vouchers.
     *
     * @return Factory|View
     */
    public function index()
    {
        $vouchers = Voucher::orderBy('id', 'desc')->paginate(50);
        return view('service.vouchers.index', compact('vouchers'));
    }

    /**
     * Display a single Voucher.
     *
     * @return Application|Factory|View
     */
    public function search(VoucherSearchRequest $request)
    {
        if (isset($request['search'])) {
            $voucher_code = $request->get('voucher_code');
            $vouchers = Voucher::where('code', $voucher_code)->get();
        } elseif (isset($request['reset'])) {
            $vouchers = Voucher::orderBy('id', 'desc')->paginate(50);
        }
        return view('service.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating new Vouchers.
     *
     * @return Factory|View
     */
    public function create()
    {
        // A range of numbers from first to last.
        // Last can be empty. Then only one is created.
        $sponsors = Sponsor::all();
        return view('service.vouchers.create', compact('sponsors'));
    }

    /**
     * Show the void Vouchers form
     *
     * @return Factory|view
     */
    public function void()
    {
        return view('service.vouchers.void');
    }

    /**
     * Show the edit Vouchers form
     *
     * @return Factory|view
     */
    public function viewOne($id)
    {
        $voucher = Voucher::where('id', $id)
            ->with('history')
            ->first();
        return view('service.vouchers.edit', ['voucher' => $voucher]);
    }

    /**
     * Retire voucher range - through void or expire
     *
     * @param AdminUpdateVoucherRequest $request
     * @return RedirectResponse
     */
    public function retireBatch(AdminUpdateVoucherRequest $request): RedirectResponse
    {
        $rangeDef = Voucher::createRangeDefFromVoucherCodes(
            $request->input('voucher-start'),
            $request->input('voucher-end')
        );

        // Fetch once, reuse
        $voidableCodes = Voucher::inDefinedRange($rangeDef)
            ->inVoidableState()
            ->pluck('code');

        if ($voidableCodes->isEmpty()) {
            return redirect()
                ->route('admin.vouchers.void')
                ->withInput()
                ->with('error_message', trans('service.messages.vouchers_batchretiretransition.blocked'));
        }

        $allCodes = Voucher::inDefinedRange($rangeDef)->pluck('code');

        $transitions = [
            Voucher::createTransitionDef('dispatched', $request->input('transition')),
        ];

        $transitions[] = Voucher::createTransitionDef(
            $transitions[0]->to,
            'retire'
        );

        try {
            DB::transaction(static function () use ($rangeDef, $transitions) {

                $nowTime  = now();
                $user     = auth()->user();
                $userId   = $user->id;
                $userType = class_basename($user);

                foreach ($transitions as $transitionDef) {
                    Voucher::inDefinedRange($rangeDef)
                        ->inOneOfStates([$transitionDef->from])
                        ->orderBy('id')
                        ->chunkById(2000, function ($vouchers) use (
                            $nowTime,
                            $userId,
                            $userType,
                            $transitionDef
                        ) {
                            VoucherState::batchInsert(
                                $vouchers,
                                $nowTime,
                                $userId,
                                $userType,
                                $transitionDef
                            );

                            Voucher::whereKey($vouchers->modelKeys())
                                ->update([
                                    'currentState' => $transitionDef->to,
                                ]);
                        });
                }
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __METHOD__, [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.vouchers.void')
                ->withInput()
                ->with('error_message', 'Database error, unable to transition vouchers.');
        }

        $successCodes = implode(' ', $voidableCodes->all());
        $failedCodes  = implode(
            ' ',
            array_diff($allCodes->all(), $voidableCodes->all())
        );

        $notificationMsg = trans(
            'service.messages.vouchers_batchretiretransition.success',
            [
                'transition_to'    => end($transitions)->to,
                'success_codes'    => $successCodes,
                'fail_code_details'=> $failedCodes
                    ? "{$failedCodes} could not be retired."
                    : '',
            ]
        );

        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notificationMsg);
    }

    /**
     * Store a new Voucher range.
     *
     * @param AdminNewVoucherRequest $request
     * @return RedirectResponse
     */
    public function storeBatch(AdminNewVoucherRequest $request): RedirectResponse
    {
        // Setup some variables
        $input = $request->all();
        $sponsor = Sponsor::findOrFail($input['sponsor_id']);
        $now_time = Carbon::now();
        $maxStep = 1000;
        // We're going to ignore what they typed and use the calculated serials.
        $start = $input['start-serial'];
        $end = $input['end-serial'];

        // Calculate the number of codes we need
        $numCodes = $end - $start;

        $step = 1;
        if ($numCodes > 1) {
            // Calculate the step, max = $maxStep.
            $step = ($numCodes < $maxStep)
                ? $numCodes
                : $maxStep;
        }

        // Setup the chunks
        $chunks = range(
            $start,
            $end,
            $step
        );

        // Add the range to the end.
        if (!in_array($end, $chunks)) {
            $chunks[] = $end;
        }

        // For each chunk, create the integers in that set.
        foreach ($chunks as $k => $chunkStart) {
            // Reset New Vouchers
            $new_vouchers = [];

            $chunkEnd = (isset($chunks[$k + 1]))
                ? $chunks[$k + 1] - 1
                : $end;
            $currentChunk = range($chunkStart, $chunkEnd);

            foreach ($currentChunk as $c) {
                $v = new Voucher();
                $v->code = $sponsor->shortcode .
                    // String pad to *5* places, eg 00001 to 09999 to 10000 to 10001
                    str_pad(
                        $c,
                        5,
                        "0",
                        STR_PAD_LEFT
                    );
                $v->sponsor_id = $sponsor->id;
                $v->currentstate = 'printed';
                $v->created_at = $now_time;
                $v->updated_at = $now_time;
                $new_vouchers[] = $v->attributesToArray();
                unset($v);
            }
            // Batch insert raw vouchers.
            // Todo : there's NO "this voucher already exists" checking!!
            Voucher::insert($new_vouchers);
        }

        $notification_msg = trans('service.messages.vouchers_create.success', [
            'shortcode' => $sponsor->shortcode,
            'start' => $request['start'],
            'end' => $request['end'],
        ]);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notification_msg);
    }
}

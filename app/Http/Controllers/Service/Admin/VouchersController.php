<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewVoucherRequest;
use App\Http\Requests\AdminUpdateVoucherRequest;
use App\Sponsor;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Log;

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
     * Update Voucher range - specifically, state
     *
     * @param AdminUpdateVoucherRequest $request
     * @return RedirectResponse
     */
    public function updateBatch(AdminUpdateVoucherRequest $request)
    {
        // Make a rangeDef
        $rangeDef = Voucher::createRangeDefFromVoucherCodes($request->input('voucher-start'), $request->input('voucher-end'));

        // Check the voucher range is clear to be voided.
        if (!Voucher::rangeIsVoidable($rangeDef)) {
            // Whoops! Some of the vouchers may have not be voidable
            // TODO : report problem voucher ranges.
            return redirect()
                ->route('admin.deliveries.create')
                ->withInput()
                ->with('error_message', trans('service.messages.vouchers_voidexpire_blocked'));
        };

        // Create and reset the transitions route to start point
        $transitions[] = Voucher::createTransitionDef("dispatched", $request->input("transition"));
        $transitions[] = Voucher::createTransitionDef(end($transitions)->to, 'retire');
        reset($transitions);

        // Update vouchers or roll back
        try {
            DB::transaction(function () use ($rangeDef, $transitions) {

                // Some things we'll be using.
                $now_time = Carbon::now();
                $user_id = auth()->id();
                $user_type = class_basename(auth()->user());

                foreach ($transitions as $transitionDef) {
                    // Bulk update VoucherStates for speed.
                    Voucher::select('id')
                        ->withRangedVouchersInState($rangeDef, $transitionDef->from)
                        ->chunk(
                            // Should be big enough chunks to avoid memory problems
                            10000,
                            // Closure only has 1 param...
                            function ($vouchers) use ($now_time, $user_id, $user_type, $transitionDef) {
                                // ... but method needs 'em all.
                                VoucherState::batchInsert($vouchers, $now_time, $user_id, $user_type, $transitionDef);
                            }
                        )
                    ;
                }
                // Get all the vouchers in the range and update them with the end state
                Voucher::withRangedVouchersInState($rangeDef, end($transitions)->from)
                    ->update(['currentState' => end($transitions)->to])
                ;
            });
        } catch (\Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getMessage()); // Log original error message too
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.deliveries.create')
                ->withInput()
                ->with('error_message', 'Database error, unable to transition a voucher.');
        }

        // Prepare the message
        $notification_msg = trans('service.messages.vouchers_voidexpire_success', [
            'transition_to' => end($transitions)->to,
            'shortcode' => $rangeDef->shortcode,
            'start' => $rangeDef->start,
            'end' => $rangeDef->end,
        ]);

        // Send it.
        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notification_msg)
            ;
    }


    /**
     * Store Voucher range.
     *
     * @param AdminNewVoucherRequest $request
     * @return RedirectResponse
     */
    public function storeBatch(AdminNewVoucherRequest $request)
    {
        // Setup some variables
        $input = $request->all();
        $user_id = auth()->id();
        $user_type = class_basename(auth()->user());
        $sponsor = Sponsor::findOrFail($input['sponsor_id']);
        $now_time = Carbon::now();
        $maxStep = 1000;
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
                // left pad the code to the length of the raw "end" with zeros.
                    str_pad(
                        $c,
                        strlen($input['end']),
                        "0",
                        STR_PAD_LEFT
                    );
                $v->sponsor_id = $sponsor->id;
                // Set straight to printed; we're faking the process for speed.
                $v->currentstate = 'printed';
                $v->created_at = $now_time;
                $v->updated_at = $now_time;
                $new_vouchers[] = $v->attributesToArray();
                unset($v);
            }
            // Batch insert raw vouchers.
            // Todo : there's NO "this voucher already exists" checking!!
            Voucher::insert($new_vouchers);

            // For each ID we just made
            $vouchers = Voucher::where('created_at', '=', $now_time)
                ->where('updated_at', '=', $now_time)
                ->where('currentstate', '=', 'requested')
            ;
            $states = [];

            // Build a set of big arrays to insert in one go
            foreach ($vouchers as $voucher) {
                // create VoucherState
                $s = new VoucherState();

                // Set initial attributes
                $s->transition = 'order';
                $s->from = 'requested';
                $s->voucher_id = $voucher->id;
                $s->to = 'ordered';
                $s->created_at = $now_time;
                $s->updated_at = $now_time;
                $s->source = "";
                $s->user_id = $user_id; // the user ID
                $s->user_type = $user_type; // the type of user

                // Add them to the array.
                $states[] = $s->attributesToArray();

                // Update attributes
                $s->transition = 'print';
                $s->from = 'ordered';
                $s->to = 'printed';

                // Add them to the array again
                $states[] = $s->attributesToArray();
                unset($s);
            }
            // Insert states
            VoucherState::insert($states);
            // printed vouchers should now be redeemable.
        }

        $notification_msg = trans('service.messages.vouchers_create_success', [
            'shortcode' => $sponsor->shortcode,
            'start' => $request['start'],
            'end' => $request['end'],
        ]);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notification_msg)
            ;
    }
}

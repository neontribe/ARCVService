<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminIndexDeliveriesRequest;
use App\Http\Requests\AdminNewDeliveryRequest;
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
use Throwable;

class DeliveriesController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @param AdminIndexDeliveriesRequest $request
     * @return Factory|View
     */
    public function index(AdminIndexDeliveriesRequest $request)
    {
        // load the deliveries.
        $deliveries = Delivery::with('centre')
            ->orderByField($request->all(['orderBy', 'direction']))
            ->get();

        return view('service.deliveries.index', compact('deliveries'));
    }

    /**
     * Show the form for sending batches of vouchers.
     *
     * @return Factory|View
     */
    public function create()
    {
        $sponsors = Sponsor::get();

        return view('service.deliveries.create', compact('sponsors'));
    }

    /**
     * @throws Throwable
     */
    public function store(AdminNewDeliveryRequest $request): RedirectResponse
    {
        // Make a rangeDef
        $rangeDef = Voucher::createRangeDefFromVoucherCodes(
            $request->input('voucher-start'),
            $request->input('voucher-end')
        );

        // Ensure vouchers in range are deliverable
        if (!Voucher::rangeIsDeliverable($rangeDef)) {
            // Whoops! Some of the vouchers may have been delivered
            return redirect()
                ->route('admin.deliveries.create')
                ->withInput()
                ->with('error_message', trans('service.messages.vouchers_delivery.blocked'));
        }

        // Get centre
        $centre = Centre::findOrFail($request->input('centre'));

        // Define allowed transition(s)
        $transitions = [Voucher::createTransitionDef('printed', 'dispatch')];

        // Create delivery or roll back
        try {
            DB::transaction(static function () use ($rangeDef, $transitions, $centre, $request) {

                $delivery = Delivery::create([
                    'centre_id' => $centre->id,
                    'range' => $rangeDef->shortcode . $rangeDef->start .
                        " - " .
                        $rangeDef->shortcode . $rangeDef->end,
                    'dispatched_at' => Carbon::createFromFormat('Y-m-d', $request->input('date-sent')),
                ]);

                $nowTime  = $delivery->created_at;
                $user     = auth()->user();
                $userId   = $user->id;
                $userType = class_basename($user);

                foreach ($transitions as $transitionDef) {
                    Voucher::whereNull('delivery_id')
                        ->inDefinedRange($rangeDef)
                        ->inOneOfStates(['printed'])
                        ->orderBy('id')
                        ->chunkById(2000, function ($vouchers) use (
                            $nowTime,
                            $userId,
                            $userType,
                            $transitionDef,
                            $delivery
                        ) {
                            // Insert state history records
                            VoucherState::batchInsert(
                                $vouchers,
                                $nowTime,
                                $userId,
                                $userType,
                                $transitionDef
                            );

                            // Update vouchers atomically
                            Voucher::whereIn('id', $vouchers->pluck('id'))
                                ->update([
                                    'delivery_id'  => $delivery->id,
                                    'currentState' => $transitionDef->to,
                                ]);
                        });
                }
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getMessage()); // Log original error message too
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.deliveries.create')
                ->withInput()
                ->with('error_message', 'Database error, unable to create a delivery');
        }
        // Success
        return redirect()
            ->route('admin.deliveries.index')
            ->with('message', trans('service.messages.vouchers_delivery.success', ['centre_name' => $centre->name]));
    }
}

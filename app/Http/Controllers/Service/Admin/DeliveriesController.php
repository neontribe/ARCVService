<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewDeliveryRequest;
use App\Sponsor;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;
use Throwable;

class DeliveriesController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @param Request $request
     * @return Factory|View
     */
    public function index(Request $request)
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
     * @param AdminNewDeliveryRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(AdminNewDeliveryRequest $request)
    {
        // Make a rangeDef
        $rangeDef = Voucher::createRangeDefFromVoucherCodes($request->input('voucher-start'), $request->input('voucher-end'));

        // Check the voucher range is clear to be delivered.
        if (!Voucher::rangeIsDeliverable($rangeDef)) {
            // Whoops! Some of the vouchers may have been delivered
            // TODO : report problem voucher ranges.
            return redirect()
                ->route('admin.deliveries.create')
                ->withInput()
                ->with('error_message', trans('service.messages.vouchers_delivery.blocked'));
        };

        // Get centre
        $centre = Centre::findOrFail($request->input('centre'));

        // Make a transition definition
        $transitions[] = Voucher::createTransitionDef("printed", "dispatch");

        // Create delivery or roll back
        try {
            DB::transaction(function () use ($rangeDef, $transitions, $centre, $request) {

                $delivery = Delivery::create([
                    'centre_id' => $centre->id,
                    'range' => $rangeDef->shortcode . $rangeDef->start .
                        " - " .
                        $rangeDef->shortcode . $rangeDef->end,
                    'dispatched_at' => Carbon::createFromFormat('Y-m-d', $request->input('date-sent')),
                ]);

                $now_time = $delivery->created_at;
                $user_id = auth()->id();
                $user_type = class_basename(auth()->user());

                // Bulk update VoucherStates for speed.
                foreach ($transitions as $transitionDef) {
                    DB::enableQueryLog();
                    Voucher::select('id')
                        ->whereNull('delivery_id')
                        ->inDefinedRange($rangeDef)
                        ->inOneOfStates(['printed'])
                        ->chunk(
                            // should be big enough chunks to avoid memory problems
                            10000,
                            // Closure only has 1 param...
                            function ($vouchers) use ($now_time, $user_id, $user_type, $transitionDef, $delivery) {
                                // ... but method needs 'em.
                                VoucherState::batchInsert($vouchers, $now_time, $user_id, $user_type, $transitionDef);
                                // Get all the vouchers in the current chunk and update them with the delivery Id and state
                                Voucher::whereIn('id', $vouchers->pluck('id'))
                                    ->update([
                                        'delivery_id' => $delivery->id,
                                        'currentState' => $transitionDef->to
                                    ]);
                            }
                        );
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

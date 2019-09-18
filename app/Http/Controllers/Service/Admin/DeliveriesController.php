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
     * Determines if the given voucher range contains entries already delivered
     *
     * @param $start
     * @param $end
     * @param $shortcode
     * @return bool
     * @throws Throwable
     */
    public static function rangeIsUndelivered($start, $end, $shortcode)
    {
        $undeliveredRanges = Voucher::getUndeliveredVoucherRangesByShortCode($shortcode);

        // If NOT an empty array
        if (!empty($undeliveredRanges)) {
            foreach ($undeliveredRanges as $undeliveredRange) {
                // Are Start and End both in the range?
                if ($start >= $undeliveredRange->initial_serial &&
                    $end <= $undeliveredRange->serial &&
                    $start >= $end ) {
                    return true;
                };
            }
        }
        // Start and End within none of the free ranges.
        return false;
    }

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
        // Get centre
        $centre = Centre::findOrFail($request->input('centre'));

        $startCode = $request->input('voucher-start');
        $endCode = $request->input('voucher-end');

        // Break the codes up.
        $start = Voucher::splitShortcodeNumeric($startCode);
        $start["number"] = intval($start["number"]);

        $end = Voucher::splitShortcodeNumeric($endCode);
        $end["number"] = intval($end["number"]);

        // Check the voucher range is clear to be delivered.
        if (!self::rangeIsUndelivered($start["number"], $end["number"], $start["shortcode"])) {
            // Whoops! Some of the vouchers may have been delivered
            return redirect()
                ->route('admin.deliveries.create')
                ->with('error_message', 'The voucher range given contains some vouchers that have already been delivered.');
        };

        // Create delivery or roll back
        try {
            DB::transaction(function () use ($start, $end, $centre, $request) {

                $delivery = Delivery::create([
                    'centre_id' => $centre->id,
                    'range' => $start[0] . " - " . $end[0],
                    'dispatched_at' => Carbon::createFromFormat('Y-m-d', $request->input('date-sent')),
                ]);

                $now_time = $delivery->created_at;
                $user_id = auth()->id();
                $user_type = class_basename(auth()->user());

                // Bulk update VoucherStates for speed.
                Voucher::select('id')
                    ->where('currentState', 'printed')
                    ->whereNull('delivery_id')
                    ->whereBetween(
                        DB::raw("cast(replace(code, '{$start["shortcode"]}', '') as signed)"),
                        [$start["number"], $end["number"]]
                    )->chunk(
                        // should be big enough chunks to avoid memory problems
                        10000,
                        function ($vouchers) use ($now_time, $user_id, $user_type) {
                            $states =[];
                            // create VoucherState
                            foreach ($vouchers as $voucher) {
                                $s = new VoucherState();

                                // Set initial attributes
                                $s->transition = 'dispatch';
                                $s->from = 'printed';
                                $s->voucher_id = $voucher->id;
                                $s->to = 'dispatched';
                                $s->created_at = $now_time;
                                $s->updated_at = $now_time;
                                $s->source = "";
                                $s->user_id = $user_id; // the user ID
                                $s->user_type = $user_type; // the type of user

                                // Add them to the array.
                                $states[] = $s->attributesToArray();
                            }
                            // Insert this batch of vouchers.
                            VoucherState::insert($states);
                        }
                    );

                // Get all the vouchers in the range and update them with the delivery Id and state
                Voucher::where('currentState', 'printed')
                    ->whereNull('delivery_id')
                    ->whereBetween(
                        DB::raw("cast(replace(code, '{$start["shortcode"]}', '') as signed)"),
                        [$start["number"], $end["number"]]
                    )
                    ->update([
                        'delivery_id' => $delivery->id,
                        'currentState' => 'dispatched'
                        ]);
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
        }
        // Success
        return redirect()
            ->route('admin.deliveries.index')
            ->with('message', 'Delivery to ' . $centre->name . ' created.');
    }
}
<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewDeliveryRequest;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;
use Throwable;

class DeliveriesController extends Controller
{
    /**
     * Determines if the given voucher range contains entries already delivered.
     *
     * @param $startCode
     * @param $endCode
     * @return bool
     */
    public static function rangeIsUndelivered($startCode, $endCode)
    {
        // Break the codes up.
        $start = Voucher::splitShortcodeNumeric($startCode);
        $start["number"] = intval($start["number"]);

        $end = Voucher::splitShortcodeNumeric($endCode);
        $end["number"] = intval($end["number"]);

        // Get the Undelivered ranges
        try {
            $undeliveredRanges = Voucher::getUndeliveredVoucherRangesByShortCode($start["shortcode"]);
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            abort(500);
        }

        // That cold *possibly* be empty of anything...
        if (empty($undeliveredRanges)) {
            return false;
        }

        foreach ($undeliveredRanges as $undeliveredRange) {
            // Are Start and End both in the range?
            if ($start['number'] >= $undeliveredRange['initial_serial'] &&
                $start['number'] <= $undeliveredRange['final_serial'] &&
                $end['number'] >= $undeliveredRange['initial_serial'] &&
                $end['number'] <= $undeliveredRange['final_serial'] &&
                $start['number'] >= $end['number']) {
                return true;
            };
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

    public function store(AdminNewDeliveryRequest $request)
    {
        // Get centre
        $centre = Centre::findOrFail($request->input('centre'));

        // Create delivery
        try {
            $delivery = DB::transaction(function () use ($request, $centre) {
                // OR, if we get all the undelivered vouchers in this range, are they

                $startCode = $request->input('voucher-start');
                $endCode = $request->input('voucher-end');

                // Is every voucher in the specified range ok to deliver?
                if (!self::rangeIsUndelivered($startCode, $endCode)) {
                    return false;
                };

                $delivery = Delivery::create([
                    'centre_id' => $centre->id,
                    'range' => $startCode . " - " . $endCode,
                    'dispatched_at' => Carbon::createFromFormat('Y-m-d', $request->input('date-sent')),
                ]);

                // Add vouchers to delivery
                // transition to dispatched

                return $delivery;
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user

            return redirect()
                ->route('admin.deliveries.index')
                ->with('message', 'Delivery to ' . $centre->name . ' created.');
        }
    }
}
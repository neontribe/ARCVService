<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewDeliveryRequest;
use App\Sponsor;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

class DeliveriesController extends Controller
{
    public static function getDeliveredVoucherRangesByShortCode(string $shortcode)
    {
        try {
            $deliveries = DB::transaction(function () use ($shortcode) {
                DB::statement(DB::raw('SET @initial_id, @initial_serial=0, SET @previous=0;'));

                $result = DB::select(
                    "
                    SELECT
                        t1.*, 
                        v1.code as intitial_code,
                        v2.code as final_code
                    FROM (
                        
                        SELECT
                            @initial_serial := if(serial - @previous = 1, @initial_serial, serial) as initial_serial,
                            @initial_id := if(serial - @previous = 1, @initial_id, id) as initial_id,
                            @previous := serial as serial,
                            id as final_id
                        FROM (
                        
                            SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as serial
                            FROM vouchers
                            WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                              AND delivery_id is null
                            ORDER BY serial
                        
                        ) as t5
                    
                    ) AS t1
                        INNER JOIN (
                    
                            SELECT initial_serial, max(serial) as final_serial
                            FROM (
                    
                                 SELECT
                                     @initial_serial := if(serial - @previous = 1, @initial_serial, serial) as initial_serial,
                                     @initial_id := if(serial - @previous = 1, @initial_id, id) as initial_id,
                                     @previous := serial as serial,
                                     id
                                 FROM (
                                      SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as serial
                                      FROM vouchers
                                      WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                                        AND delivery_id is null
                                      ORDER BY serial
                    
                                 ) as t4
                    
                            ) as t3
                            GROUP BY initial_serial
                    
                        ) as t2
                        ON t1.initial_serial = t2.initial_serial
                          AND t1.serial = t2.final_serial
                    
                    LEFT JOIN vouchers as v1
                        ON initial_id = v1.id
                    
                    LEFT JOIN vouchers as v2
                        ON final_id = v2.id
                    "
                );
            });
        } catch (Exception $e) {
            // do something
        }
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
        try {
            $delivery = DB::transaction(function () use ($request) {

                // Update a CentreUser;
                $centre = Centre::findOrFail($request->input('centre'));
                $dispatched_at = Carbon::createFromFormat('Y-m-d', $request->input('date-sent'));

                $delivery = Delivery::create([
                    'centre_id' => $centre->id,
                    'range' => '',
                    'dispatched_at' => $dispatched_at,
                ]);

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
        // Create delivery

        // Create ranges of deliverable vouchers
        // they are printed
        // they are not on a delivery

        // If ranges have problems, fail and say why

        // Progress state of each voucher in the range to delvierable
    }
}
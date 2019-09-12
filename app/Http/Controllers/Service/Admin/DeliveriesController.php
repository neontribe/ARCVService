<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Requests\AdminNewDeliveryRequest;
use App\Sponsor;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

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
                    'dispatched_at'=> $dispatched_at
                ]);

                $range

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
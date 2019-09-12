<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Http\Requests\AdminNewDeliveryRequest;
use App\Sponsor;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $centre = Centre::findOrFail($request->input('centre'));

        return redirect()
            ->route('admin.deliveries.index')
            ->with('message', 'Delivery to ' . $centre->name . ' created.');
    }
}
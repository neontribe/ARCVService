<?php

namespace App\Http\Controllers\Service\Admin;

use App\Delivery;
use App\Sponsor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveriesController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $sponsors = Sponsor::get();

        return view('service.deliveries.create', compact('sponsors'));
    }

    public function store()
    {
        // implement store
    }
}
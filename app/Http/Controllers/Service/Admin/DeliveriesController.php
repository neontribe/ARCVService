<?php

namespace App\Http\Controllers\Service\Admin;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Sponsor;

class DeliveriesController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $deliveries = Delivery::get();

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
        // impelment store
    }
}
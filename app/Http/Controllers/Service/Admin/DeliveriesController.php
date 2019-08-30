<?php

namespace App\Http\Controllers\Service\Admin;
use App\Http\Controllers\Controller;
use App\Sponsor;

class DeliveriesController extends Controller
{
    // /**
    //  * Display a listing of Sponsors.
    //  *
    //  * @return json
    //  */
    // public function index()
    // {
    //     $sponsors = Sponsor::get();
    //     return view('service.sponsors.index', compact('sponsors'));
    // }

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
}
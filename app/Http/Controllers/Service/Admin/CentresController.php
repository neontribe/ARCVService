<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Centre;
use App\Sponsor;

class CentresController extends Controller
{

    /**
     * Display a listing of Centres.
     *
     * @return json
     */
    public function index()
    {
        $centres = Centre::get();

        return view('service.centres.index', compact('centres'));
    }

    /**
     * Show the form for creating new Centres.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $sponsors = Sponsor::get();

        return view('service.centres.create', compact('sponsors'));
    }
}

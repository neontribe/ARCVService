<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Sponsor;

class SponsorsController extends Controller
{

    /**
     * Display a listing of Sponsors.
     *
     * @return json
     */
    public function index()
    {
        $sponsors = Sponsor::get();

        return view('service.sponsors.index', compact('sponsors'));
    }

      /**
     * Show the form for creating new Workers.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        // WE NEED LIST OF CENTRES

        // ALSO NEIGHBOURING CENTRES TO THE SELECTED ONE - IF WE WANT TO BE CLEVER
        // IF NOT - FULL LIST OF CENTRES
        return view('service.sponsors.create');
    }
}

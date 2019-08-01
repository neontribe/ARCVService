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
     * Show the form for creating new Sponsors.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        // I DONT THINK WE REALLY NEED ANYTHING ELSE.
        return view('service.sponsors.create');
    }
}

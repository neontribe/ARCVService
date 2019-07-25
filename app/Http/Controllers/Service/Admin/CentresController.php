<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Centre;

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
}

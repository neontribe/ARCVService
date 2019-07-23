<?php

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
        $centres = Centre::orderBy('id', 'desc')->paginate(50);
        return view('service.centres.centres_view', compact('centres'));
    }
}
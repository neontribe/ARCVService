<?php

namespace App\Http\Controllers\Service;

use App\Market;
use App\Http\Controllers\Controller;

class MarketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Market::all();
    }
}

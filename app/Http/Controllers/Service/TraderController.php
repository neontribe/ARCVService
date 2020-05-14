<?php

namespace App\Http\Controllers\Service;

use App\Trader;
use App\Http\Controllers\Controller;

class TraderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Trader::all();
    }
}

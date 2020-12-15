<?php

namespace App\Http\Controllers\Service\Data;

use App\Trader;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class TraderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Trader[]|Collection
     */
    public function index()
    {
        return Trader::all();
    }
}

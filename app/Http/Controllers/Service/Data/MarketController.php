<?php

namespace App\Http\Controllers\Service\Data;

use App\Market;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class MarketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Market[]|Collection
     */
    public function index()
    {
        return Market::all();
    }
}

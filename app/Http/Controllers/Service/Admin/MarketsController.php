<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Market;
use App\Sponsor;

class MarketsController extends Controller
{
    public function index()
    {
        $markets = Market::get();
        return view('service.markets.index', compact('markets'));
    }

    public function store()
    {
        //
    }

    public function create()
    {
        $sponsors = Sponsor::get();
        return view('service.markets.create', compact('sponsors'));
    }

    public function edit($id)
    {
        $market = Market::findOrFail($id);
        $sponsors = Sponsor::get();
        return view('service.markets.edit', compact('sponsors', 'market'));
    }

    public function update()
    {
        //
    }
}
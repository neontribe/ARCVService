<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Sponsor;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class SponsorsController extends Controller
{

    /**
     * Display a listing of Sponsors.
     *
     * @return Factory|View
     */
    public function index()
    {
        $sponsors = Sponsor::get();

        return view('service.sponsors.index', compact('sponsors'));
    }

      /**
     * Show the form for creating new Sponsors.
     *
     * @return Factory|View
     */
    public function create()
    {
        return view('service.sponsors.create');
    }

    public function store(AdminNewSponsorRequest $request)
    {
        return view('service.sponsors.index');
    }
}

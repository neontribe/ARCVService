<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\CentreUser;

class WorkersController extends Controller
{

    /**
     * Display a listing of Workers.
     *
     * @return json
     */
    public function index()
    {
        $workers = CentreUser::get();

        return view('service.workers.index', compact('workers'));
    }
}

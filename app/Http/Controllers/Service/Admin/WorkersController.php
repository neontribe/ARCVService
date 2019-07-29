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

     /**
     * Show the form for creating new Workers.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // WE NEED LIST OF CENTRES

        // ALSO NEIGHBOURING CENTRES TO THE SELECTED ONE - IF WE WANT TO BE CLEVER
        // IF NOT - FULL LIST OF CENTRES
        return view('service.workers.create');
    }

   
}

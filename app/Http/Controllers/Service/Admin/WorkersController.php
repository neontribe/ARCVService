<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\CentreUser;

class WorkersController extends Controller
{

    /**
     * Display a listing of Workers.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $workers = CentreUser::get();

        return view('service.workers.index', compact('workers'));
    }

     /**
     * Show the form for creating new Workers.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        // WE NEED LIST OF CENTRES

        // ALSO NEIGHBOURING CENTRES TO THE SELECTED ONE - IF WE WANT TO BE CLEVER
        // IF NOT - FULL LIST OF CENTRES
        return view('service.workers.create');
    }

    public function edit($id)
    {
        $worker = CentreUser::findOrFail($id);

        return view('service.workers.edit', compact('worker'));
    }
    
    public function update($id)
    {
        $worker = CentreUser::where('id', '=', $id);

        return view('service.workers.create');
    }
}

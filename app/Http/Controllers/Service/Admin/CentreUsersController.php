<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreUserRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class CentreUsersController extends Controller
{
    /**
     * Display a listing of Workers.
     *
     * @return Factory|View
     */
    public function index()
    {
        $workers = CentreUser::get();
        return view('service.centreusers.index', compact('workers'));
    }

     /**
     * Show the form for creating new Workers.
     *
     * @return Factory|View
     */
    public function create()
    {
        $centres = Centre::get(['name','id']);
        return view('service.centreusers.create', compact('centres'));
    }

    public function store(AdminNewCentreUserRequest $request)
    {
        // Code to store a centreUser
    }
}

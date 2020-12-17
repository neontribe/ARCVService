<?php

namespace App\Http\Controllers\Service\Data;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return User[]|Collection
     */
    public function index()
    {
        return User::all();
    }
}

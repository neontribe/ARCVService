<?php

namespace App\Http\Controllers\Store;

use App\Family;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use URL;

class DashboardController extends Controller
{
    /**
     * Index the Dashboard options
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        if (!Auth::check()) {
            redirect(URL::route('store.login'));
        }

        $user = Auth::user();
        $centre = $user->centre;

        $data = [
            "user" => $user,
            "user_name" => $user->name,
            "centre_name" => $centre->name ?? null,
            "centre_id" => $centre ? $centre->id : null,
            "pref_collection" => ($centre->print_pref === 'collection'),
            "programme" => $user->centre->sponsor->programme,
        ];
        return view('store.dashboard', $data);
    }
}

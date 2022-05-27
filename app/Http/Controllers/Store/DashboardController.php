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

        $pref_collection = ($centre->print_pref === 'collection');

        $centre_id = $centre ? $centre->id : null;

        $print_route = ($pref_collection) ?
            URL::route('store.centre.registrations.collection', $centre_id) :
            URL::route('store.registrations.print');

        $programme = $user->centre->sponsor->programme;

        $data = [
            "user" => $user,
            "user_name" => $user->name,
            "centre_name" => $centre ? $centre->name : null,
            "centre_id" => $centre_id,
            "print_button_text" => $pref_collection ? 'Print collection sheet' : 'Print all ' . Family::getAlias() . ' sheets',
            "print_route" => $print_route,
            "programme" => $programme,
        ];
        return view('store.dashboard', $data);
    }
}

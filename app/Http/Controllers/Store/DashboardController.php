<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Auth;
use URL;

class DashboardController extends Controller
{
    /**
     * Index the Dashboard options
     */
    public function index()
    {
        $user = Auth::user();
        $centre = $user->centre;

        $pref_collection = ($centre->print_pref === 'collection');

        $centre_id = $centre ? $centre->id : null;

        $print_route = ($pref_collection) ?
            URL::route('store.centre.registrations.collection', $centre_id) :
            URL::route('store.registrations.print');

        $data = [
            "user" => $user,
            "user_name" => $user->name,
            "centre_name" => $centre ? $centre->name : null,
            "centre_id" => $centre_id,
            "print_button_text" => $pref_collection ? 'Print collection sheet' : 'Print all family sheets',
            "print_route" => $print_route,
        ];
        return view('store.dashboard', $data);
    }
}

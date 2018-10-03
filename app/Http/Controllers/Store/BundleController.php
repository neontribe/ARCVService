<?php

namespace App\Http\Controllers\Store;

use App\Registration;
use App\Http\Controllers\Controller;

class BundleController extends Controller
{
    /**
     * Returns the voucher-manager page for a given registration
     *
     * @param Registration $registration
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */

    public function create(Registration $registration)
    {
        $user = Auth::user();
        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
        ];

        array_merge($data, [
            "family" => $registration->family(),
            "bundles" => $registration->bundles()
        ]);

        return view('', $data);
    }

    /**
     * List all the bundles
     */
    public function index()
    {
    }

}
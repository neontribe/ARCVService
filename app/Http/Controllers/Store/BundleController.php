<?php

namespace App\Http\Controllers\Store;

use Auth;
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

        // Grabs a copy of all carers
        $carers = $registration->family->carers->all();

        return view('store.manage_vouchers', array_merge(
            $data,
            [
                "registration" => $registration,
                "family" => $registration->family,
                'children' => $registration->family->children,
                "pri_carer" => array_shift($carers),
                "bundles" => $registration->bundles()
            ]
        ));
    }

    /**
     * List all the bundles
     */
    public function index()
    {
    }

}

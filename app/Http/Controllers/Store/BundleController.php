<?php

namespace App\Http\Controllers\Store;

use Auth;
use App\Registration;
use Illuminate\Http\Request;
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

        return view('store.manage_vouchers', $data);
    }

    /**
     * List all the bundles
     */
    public function index()
    {
    }

    /**
     * Create OR Upate a registrations current active bundle
     */
    public function update(Request $request, Registration $registration)
    {
        // validate the incoming data
        // fetch or create a current bundle for registration

        $bundle = $registration->currentBundle();

        // sync_vouchers.
        if ($bundle->syncVouchers($request->codes)) {
            return;
        } else {
            return;
        };
    }

}
<?php

namespace App\Http\Controllers\Store;

use Auth;
use App\Voucher;
use App\Registration;
use App\Http\Requests\StoreUpdateBundleRequest;
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
     * Create OR Update a registrations current active bundle
     *
     * @param StoreUpdateBundleRequest $request
     * @param Registration $registration
     */
    public function update(StoreUpdateBundleRequest $request, Registration $registration)
    {
        // find our bundle.
        /** @var \App\Bundle $bundle */
        $bundle = $registration->currentBundle();

        // clean the incoming data
        $voucherCodes = (!isEmpty($request->get('vouchers')))
            ? Voucher::cleanCodes((array)$request->get('vouchers'))
            : []; // Will result in the removal of the vouchers from the bundle.

        // sync vouchers.
        $vouchers = $bundle->syncVouchers($voucherCodes);

        // will always return a collection
        if ($bundle->syncVouchers($voucherCodes)) {

        } else {
            //
            return redirect()->route('store.registration.edit')->withErrors('Registration update failed.');
        }
    }

}
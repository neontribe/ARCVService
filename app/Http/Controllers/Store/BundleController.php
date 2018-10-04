<?php

namespace App\Http\Controllers\Store;

use Auth;
use App\Bundle;
use App\Centre;
use App\Registration;
use App\Http\Requests\StoreUpdateBundleRequest;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

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
     *
     * @param StoreUpdateBundleRequest $request
     * @param Registration $registration
     */
    public function update(StoreUpdateBundleRequest $request, Registration $registration)
    {

        // fnd our bundle.
        $bundle = (!isEmpty($request->get('bundle_id')))
            ? (Bundle::find($request->get('bundle_id')))
            : $registration->currentBundle();

        // Find our disbersing centre (if any)
        $disbursing_centre = (!isEmpty($request->get('disbursing_centre_id')))
            ? (Centre::find($request->get('disbursing_centre_id')))
            : null
        ;

        // check if we need to disburse this bundle.
        if ($disbursing_centre) {
            // If wwe have one, we'll be disbersing the bundle.
            $disbursed_at = (!isEmpty($request->get('disbursed_at')))
                ? Carbon::createFromFormat(
                    'Y-m-d',
                    $request->get('disbursed_at')
                )->toDateTimeString()
                : Carbon::now();

            // TODO : need database to record carer who picked up!
            // TODO : disberse bundle.
            // $bundle->disburse
        }

        // clean the incoming data
        $voucherCodes = (!isEmpty((array)$request->get('vouchers')))
            ? Voucher::clean($voucherCodes)
            : [];

        // sync vouchers.
        if (!isEmpty($voucherCodes)) {
            $bundle->syncVouchers($voucherCodes);
        }
    }

}
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

        // Grabs a copy of all carers
        $carers = $registration->family->carers->all();
        $bundle = $registration->currentBundle()->vouchers;

        return view('store.manage_vouchers', array_merge(
            $data,
            [
                "registration" => $registration,
                "family" => $registration->family,
                "children" => $registration->family->children,
                "centre" => $registration->centre,
                "carers" => $carers,
                "pri_carer" => array_shift($carers),
                "current_vouchers" => $bundle,
                "current_vouchers_amount" => count($bundle)
            ]
        ));
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreUpdateBundleRequest $request, Registration $registration)
    {
        // find our bundle.
        /** @var \App\Bundle $bundle */
        $bundle = $registration->currentBundle();

        // clean the incoming data
        $voucherCodes = (!empty($request->get('vouchers')))
            ? Voucher::cleanCodes((array)$request->get('vouchers'))
            : []; // Will result in the removal of the vouchers from the bundle.

        // sync vouchers.
        $errors = $bundle->syncVouchers($voucherCodes);

        if (!empty($errors)) {
            $messages = [];
            // Whoops! Deal with those
            foreach ($errors as $type => $value) {
                switch ($type) {
                    case "transaction":
                        if ($value) {
                            $messages[] = 'there was a transaction error';
                        }
                        break;
                    case "transition":
                        $message[] = "there was a problem with adding/removing some vouchers";
                        // could list them
                        break;
                    case "codes":
                        $message[] = "there was a problem with some voucher codes";
                        // could list them
                        break;
                    default:
                        $messages[] = 'there was an unknown error';
                        break;
                }
            }
            // Spit the basic error messages back
            return redirect()->route('store.registration.voucher-manager', ['registration' => $registration->id])
                ->withInput()
                ->with('error_message', ucfirst(join(', ', $messages) . '.'));
        } else {
            // Otherwise, sure, return to the new view.
            return redirect()->route('store.registration.voucher-manager', ['registration' => $registration->id])
                ->with('message', 'Vouchers updated.');
        }
    }
}

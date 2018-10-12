<?php

namespace App\Http\Controllers\Store;

use Auth;
use App\Bundle;
use App\Voucher;
use App\Registration;
use App\Http\Requests\StoreAppendBundleRequest;
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

        return view('store.manage_vouchers', array_merge(
            $data,
            [
                "registration" => $registration,
                "family" => $registration->family,
                "children" => $registration->family->children,
                "centre" => $registration->centre,
                "bundles" => $registration->bundles(),
                "carers" => $carers,
                "pri_carer" => array_shift($carers)
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
     * Does a single voucher. WIP
     *
     * @param StoreAppendBundleRequest $request
     * @param Registration $registration
     * @return \Illuminate\Http\RedirectResponse
     */

    public function addVouchersToCurrentBundle(StoreAppendBundleRequest $request, Registration $registration)
    {
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // There should always be a start. The request will fail before validation before this point if there isn't
        $voucherCodes = Voucher::cleanCodes([$request->get('start')]);

        // TODO : sequential voucher add.

        return $this->redirectAfterRequest($bundle->addVouchers($voucherCodes), $registration);
    }

    /**
     * Create OR replace a registrations current active bundle
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
        return $this->redirectAfterRequest($bundle->syncVouchers($voucherCodes), $registration);
    }

    public function removeVoucherFromCurrentBundle(Registration $registration, Voucher $voucher)
    {
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // It is attached to our bundle, right?
        if ($voucher->bundle_id == $bundle->id) {
            // Call alterVouchers with no codes to check, and no bundle to detransiton and remove it.
            $errors = $bundle->alterVouchers(collect([$voucher]), [], null);
        } else {
            // Error it out (how did you get here?
            $errors["foreign"] = [$voucher->code];
        }

        return $this->redirectAfterRequest($errors, $registration);
    }

    public function redirectAfterRequest($errors, $registration)
    {
        $route = route('store.registration.voucher-manager', ['registration' => $registration->id]);
        if (!empty($errors)) {
            // Assemble messages
            $messages = [];
            foreach ($errors as $type => $values) {
                switch ($type) {
                    case "transaction":
                        if ($values) {
                            $messages[] = 'Database transaction problem.';
                        }
                        break;
                    case "transition":
                        $messages[] = "Transition problem with: " . join(', ', $values);
                        break;
                    case "codes":
                        $messages[] = "Bad code problem with: " . join(', ', $values);
                        break;
                    case "foreign":
                        $messages[] = "Action denied on a foreign voucher: " . join(', ', $values);
                        break;
                    default:
                        $messages[] = 'There was an unknown error';
                        break;
                }
            }
            // Spit the basic error messages back
            return redirect($route)
                ->withInput()
                ->with('error_message', join(', ', $messages) . '.');
        } else {
            // Otherwise, sure, return to the new view.
            return redirect($route)
                ->with('message', 'Vouchers updated.');
        }
    }
}

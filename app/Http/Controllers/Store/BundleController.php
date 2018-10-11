<?php

namespace App\Http\Controllers\Store;

use Auth;
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

    public function addVouchersToCurrentBundle(StoreAppendBundleRequest $request, Registration $registration)
    {
        $bundle = $registration->currentBundle();
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
        $errors = $bundle->syncVouchers($voucherCodes);

        if (!empty($errors)) {
            $messages = $this->assembleMessages($errors);
            // Spit the basic error messages back
            return redirect()->route('store.registration.voucher-manager', ['registration' => $registration->id])
                ->withInput()
                ->with('error_message', join('. ', $messages) . '.');
        } else {
            // Otherwise, sure, return to the new view.
            return redirect()->route('store.registration.voucher-manager', ['registration' => $registration->id])
                ->with('message', 'Vouchers updated.');
        }
    }

    /**
     * Return messages based on errors
     *
     * @param $errors
     * @return array
     */
    private function assembleMessages($errors)
    {
        $messages = [];
        foreach ($errors as $type => $values) {
            switch ($type) {
                case "transaction":
                    $messages[] = 'Database transaction problem with: ' . join(', ', $values);
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
        return $messages;
    }
}

<?php

namespace App\Http\Controllers\Store;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Voucher;
use App\Registration;
use App\Http\Requests\StoreAppendBundleRequest;
use App\Http\Requests\StoreUpdateBundleRequest;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\HtmlString;
use Log;

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

        $sorted_bundle = $bundle->sortBy('code');

        // Find the last collected bundle.
        $lastCollectedBundle = $registration->bundles()
            ->whereNotNull('disbursed_at')
            ->whereDate('disbursed_at', '<=', Carbon::today()->toDateString())
            ->orderBy('disbursed_at', 'desc')
            ->limit(1)
            ->first();
        ;

        // Turn it's disbursement date into a human date.
        $lastCollection = ($lastCollectedBundle && (!empty($lastCollectedBundle->disbursed_at)))
            // 'disbursed_at' is auto-carbon'd by the Bundle model
            ? $lastCollectedBundle->disbursed_at->format('l jS \of F Y')
            : null
        ;

        $valuation = $registration->getValuation();

        // We only need the reasons at family level.
        // TODO: move this to a Valuation if we use this elsewhere.
        $familyNoticeReasons = array_filter(
            $valuation->getNoticeReasons(),
            function ($notice) {
                return $notice["entity"] == "Family";
            }
        );

        return view('store.manage_vouchers', array_merge(
            $data,
            [
                "registration" => $registration,
                "lastCollection" => $lastCollection,
                "children" => $registration->family->children,
                "centre" => Auth::user()->centre,
                "carers" => $carers,
                "pri_carer" => array_shift($carers),
                "vouchers" => $sorted_bundle,
                "vouchers_amount" => count($bundle),
                "entitlement" => $valuation->getEntitlement(),
                "noticeReasons" => $familyNoticeReasons
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
     * Does a single or multiple voucher.
     *
     * @param StoreAppendBundleRequest $request
     * @param Registration $registration
     * @return \Illuminate\Http\RedirectResponse
     */

    public function addVouchersToCurrentBundle(StoreAppendBundleRequest $request, Registration $registration)
    {
        // Generate code range from given values (may be only 1)
        $voucherCodes = Voucher::generateCodeRange($request->get("start"), $request->get("end"));

        // Count vouchers and check them
        $numVouchers = count($voucherCodes);

        if ($numVouchers <= config('arc.bundle_max_voucher_append')) {
            // Get current Bundle
            /** @var Bundle $bundle */
            $bundle = $registration->currentBundle();
            // try to add the vouchers.
            $errors = $bundle->addVouchers($voucherCodes);
        } else {
            $errors = [ 'append' => $numVouchers ];
        }

        // Return to manager in all cases
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
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
        // Init for later
        $errors = [];

        // Default return to manager
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        // Filter inputs for only our interests
        $inputs = $request->all([
            'collected_at',
            'collected_by',
            'collected_on'
        ]);

        // If we don't mention them in form input because we are updating status of existing bundle vouchers
        if ($request->exists('vouchers')) {
            $inputs['vouchers'] = $request->input('vouchers');
        }

        /** @var \App\Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Are we updating vouchers?
        if (array_key_exists('vouchers', $inputs)) {
            // remove empty values

            $voucherCodes = array_filter(
                $inputs['vouchers'],
                function ($value) {
                    return !empty($value);
                }
            );

            $voucherCodes = (!empty($voucherCodes))
                ? Voucher::cleanCodes(($voucherCodes))
                : []; // Will result in the removal of the vouchers from the bundle.

            // sync vouchers.
            $errors[] = $bundle->syncVouchers($voucherCodes);
        }

        // Check we have values on our inputs; This should have been covered in validation...
        if (isset($inputs['collected_at']) &&
            isset($inputs['collected_by']) &&
            isset($inputs['collected_on'])
        ) {
            // Check there are actual vouchers to disburse, or this is a bit.
            if ($bundle->vouchers->count() === 0) {
                $errors["empty"] = true;
            } else {
                // Add the date;
                $bundle->disbursed_at = Carbon::createFromFormat(
                    'Y-m-d',
                    $inputs['collected_on']
                )->startOfDay()->toDateTimeString();

                try {
                    // Find and add the carer
                    $carer = Carer::findOrFail($inputs['collected_by']);
                    $bundle->collectingCarer()->associate($carer);

                    // Find and add the centre
                    $centre = Centre::findOrFail($inputs['collected_at']);
                    $bundle->disbursingCentre()->associate($centre);

                    // Add the current user as disbursingUser.
                    $bundle->disbursingUser()->associate(Auth::user());

                    // Store it.
                    $bundle->save();
                } catch (Exception $e) {
                    // Fires if finOrFail() or save() breaks
                    // Log that error by hand
                    Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
                    Log::error($e->getTraceAsString());
                    $errors['transaction'] = true;
                }

                // Return to Index as we've disbursed, and user may want to search
                $successRoute = route(
                    'store.registration.index'
                );
            }
        }

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute, $bundle);
    }

    /**
     * Function to remove all vouchers from a bundle
     *
     * @param Registration $registration
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeAllVouchersFromCurrentBundle(Registration $registration)
    {
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Get all the voucjhers for this bundle
        $vouchers = $bundle->vouchers()->get();

        // Call alterVouchers with no codes to check, and no bundle to detransiton and remove it.
        $errors = $bundle->alterVouchers($vouchers, [], null);

        // Back to manager in all cases
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
    }

    /**
     * Removes a single voucher from a bundle
     * @param Registration $registration
     * @param Voucher $voucher
     * @return \Illuminate\Http\RedirectResponse
     */
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

        // Back to manager in all cases
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
    }

    /**
     * Filters and prepares errors before returning to the voucher-manager
     *
     * @param $errors
     * @param $successRoute
     * @param $failRoute
     * @param $bundle
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectAfterRequest($errors, $successRoute, $failRoute, $bundle = null)
    {
        if (!empty($errors)) {
            // Assemble messages
            $messages = [];
            foreach ($errors as $type => $values) {
                switch ($type) {
                    case "transaction":
                        if ($values) {
                            $messages[] = 'Database transaction problem';
                        }
                        break;
                    case "transition":
                        $messages[] = "Voucher state change problem with: " . join(', ', $values);
                        break;
                    case "codes":
                        $messages[] = "These codes are invalid: " . join(', ', $values);
                        break;
                    case "disbursed":
                        $messages[] = "These vouchers have been given out: " . join(', ', $values);
                        break;
                    case "bundled":
                        // Some vouchers were allocated to a family already. Partition these based on whether the user
                        // has permission to remove them from the current family, so we can make a nice interactive
                        // error message.

                        $relevant = array();
                        $inaccessible = array();

                        foreach ($values as $voucher) {
                            $registration = $voucher->bundle->registration;

                            if (Auth::user()->isRelevantCentre($registration->centre)) {
                                // The user can deallocate the voucher from its current family at this route.
                                $route = route(
                                    'store.registration.voucher-manager',
                                    ['registration' => $registration->id]
                                );

                                $relevant[] = "<a href=\"$route\">" . e($voucher->code) . '</a>';
                            } else {
                                // The user does not have permission to remove the voucher's current allocation.
                                $inaccessible[] = $voucher->code;
                            }
                        }

                        // Generate error messages where vouchers of the sort existed, using unescaped HTML where necessary.
                        $relevant && $messages[] = new HtmlString(
                            "These vouchers are currently allocated to a different family. Click on the voucher number to view the other family's record: " . join(', ', $relevant)
                        );
                        $inaccessible && $messages[] = "These vouchers are allocated to a family in a centre you can't access: " . join(', ', $inaccessible);

                        break;
                    case "empty":
                        if ($values) {
                            $messages[] = "Action denied on empty bundle";
                        }
                        break;
                    case "append":
                        if ($values) {
                            $messages[] = "Failed adding more than ". config('arc.bundle_max_voucher_append') ." vouchers";
                        }
                        break;
                    default:
                        $messages[] = 'There was an unknown error';
                        break;
                }
            }
            // Spit the basic error messages back
            return redirect($failRoute)
                ->withInput()
                ->with('error_messages', $messages);
        } else {
            $message = "Voucher bundle updated";
            if ($bundle instanceof Bundle) {
                $numberOfVouchers = $bundle->vouchers->count();
                $fullFamily = $bundle->registration()->withFullFamily()->first();
                $familyName = $fullFamily->family->pri_carer;
                $message = 'You have just marked ' . $numberOfVouchers . ' ' . str_plural('voucher', $numberOfVouchers) . ' as collected by ' . $familyName;
            }
            // Otherwise, sure, return to the new view.
            return redirect($successRoute)
                ->with('message', $message);
        }
    }
}

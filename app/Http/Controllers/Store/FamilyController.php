<?php

namespace App\Http\Controllers\Store;

use App\Http\Requests\StoreRejoinRegistrationFamilyRequest;
use App\Http\Requests\StoreUpdateRegistrationFamilyRequest;
use Auth;
use Carbon\Carbon;
use App\Registration;
use App\Family;
use App\Http\Controllers\Controller;
use Log;

class FamilyController extends Controller
{
    /**
     * For now - the only update we can do is deactivate.
     * The rest of family related info is updated through a related Registration.
     *
     * @param StoreUpdateRegistrationFamilyRequest $request
     *
     * @param Registration $registration (because permission to update comes through Registration)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreUpdateRegistrationFamilyRequest $request, Registration $registration)
    {
        // get Family
        /** @var  $family Family */
        $family = $registration->family;

        // Set leaving date
        $family->leaving_on = Carbon::now();

        // validate by the FormRequest
        $family->leaving_reason = $request->leaving_reason;

        // Increase leave_amount by 1
        $family->increment('leave_amount');


        $family->save();
        // Successful deactivation . Go back to registration/ family search listing
        return redirect()
            ->route('store.registration.index')
            ->with('message', 'You have just marked ' . $family->carers->first()->name . ' as leaving the project');
    }

    /**
     * If the family is rejoining having previously left
     *
     * @param StoreRejoinRegistrationFamilyRequest $request
     *
     * @param Registration $registration (because permission to update comes through Registration)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejoin(StoreRejoinRegistrationFamilyRequest $request, Registration $registration): \Illuminate\Http\RedirectResponse
    {
        // get Family
        /** @var  $family Family */
        $family = $registration->family;

        // Set rejoin date
        $family->rejoin_on = Carbon::now();

        // Wipe previous leaving_on to make them 'current' again - keep original leaving_reason for
        // reports and to identify them as a previous leaver
       // $family->leaving_on = null;

        $family->save();
        // Log that this family has rejoined
        Log::info('Registration ' . $registration->id . ' marked as rejoined by service user ' . Auth::id());
        // Successful reactivation. Go back to edit this registration so details can be updated
        return redirect()
            ->route('store.registration.edit', ['registration'=> $registration->id ]);
    }

    /**
     *
     * Check status of family (active or not active)
     *
     * *
     * @param Registration $registration
     * @return bool
     */
    public static function status(Registration $registration)
    {
        // get Family
        /** @var  $family Family */
        $family = $registration->family;
        // Family is active and has never left
        if ($family->leaving_on === null && $family->rejoin_on === null) {
            $family->status = true;
        // Family is not active and has not rejoined
        } elseif ($family->leaving_on !== null && $family->rejoin_on === null) {
            $family->status = false;
        // They left then rejoined
        } elseif ($family->leaving_on < $family->rejoin_on) {
            $family->status = true;
        // They left then rejoined then left again
        } elseif ($family->leaving_on > $family->rejoin_on) {
            $family->status = false;
        }
        return $family->status;
    }
}

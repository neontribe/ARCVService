<?php

namespace App\Http\Controllers\Store;

use App\Http\Requests\StoreUpdateRegistrationFamilyRequest;
use Carbon\Carbon;
use App\Registration;
use App\Family;
use App\Http\Controllers\Controller;

class FamilyController extends Controller
{
    /**
     * For now - the only update we can do is deactivate.
     * We can also rejoin the family if they previously left.
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

        // Family has left before and is rejoining now
        if ($family->leaving_on) {
            // Add/update rejoin date
            $family->rejoin_on = Carbon::now();
            // Wipe previous leaving_on to make them 'current' again - keep original leaving_reason for
            // reports and to identify them as a previous leaver
            $family->leaving_on = null;
            $action = 'rejoining';
        // Family is now leaving again
        } elseif ($family->rejoin_on) {
            $family->rejoin_on = null;
            $family->leaving_on = Carbon::now();
            // Increase leave_amount by 1
            $family->increment('leave_amount');
            $action = 'leaving';
        // Family is leaving for the first time
        } else {
            // Set leaving date
            $family->leaving_on = Carbon::now();
            // Increase leave_amount by 1
            $family->increment('leave_amount');
            // validate by the FormRequest
            $family->leaving_reason = $request->leaving_reason;
            $action = 'leaving';
        }
        $family->save();
        // Successful leave or rejoin. Go back to registration/ family search listing
        return redirect()
            ->route('store.registration.index')
            ->with('message', 'You have just marked ' . $family->carers->first()->name . ' as ' . $action . ' the project');
    }
}

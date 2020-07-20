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
        $family->save();
        // Successful deactivation. Go back to registration/ family search listing
        return redirect()
            ->route('store.registration.index')
            ->with('message', 'You have just marked ' . $family->carers->first()->name . ' as leaving the project');
    }
}

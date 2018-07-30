<?php

namespace App\Http\Controllers\Service;

use App\Http\Requests\StoreUpdateRegistrationFamilyRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Registration;
use App\Family;
use App\Http\Controllers\Controller;
use Log;
use Gate;

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
        $family = $registration->family;

        // Set leaving date
        $family->leaving_on = Carbon::now();

        // validate by the FormRequest
        $family->leaving_reason = $request->leaving_reason;
        $family->save();
        // Successful deactivation. Go back to registration/ family search listing
        return redirect()
            ->route('service.registration.index')
            ->with('message', 'Family ' . $family->id . ' de-activated.');
    }
}

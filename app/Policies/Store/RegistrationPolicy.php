<?php

namespace App\Policies\Store;

use App\CentreUser;
use App\Registration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Log;

class RegistrationPolicy
{
    use HandlesAuthorization;

    // Permission to view a specific registration, based on centre.
    public function viewAndEdit(CentreUser $user, Registration $registration)
    {
        return true;
        //return $user->isRelevantCentre($registration->homeCentre);
    }
}

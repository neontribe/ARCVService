<?php

namespace App\Policies\Store;

use App\CentreUser;
use App\Registration;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationPolicy
{
    use HandlesAuthorization;

    // Permission to view a specific registration, based on centre.
    public function view(CentreUser $user, Registration $registration)
    {
        return $user->isRelevantCentre($registration->centre);
    }
}

<?php

namespace App\Policies\Store;

use App\CentreUser;
use App\Registration;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationPolicy
{
    use HandlesAuthorization;


    public function view(CentreUser $user, Registration $registration)
    {
        return $user->isRelevantCentre($registration->centre);
    }

    // These are permissions that we need to check a user for.
    public function updateDiary(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }

    public function updateChart(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }

    public function updatePrivacy(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }

    public function export(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }
}

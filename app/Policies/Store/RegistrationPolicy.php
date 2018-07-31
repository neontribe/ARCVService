<?php

namespace App\Policies;

use App\CentreUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationPolicy
{
    use HandlesAuthorization;

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

<?php

namespace App\Policies\Store;

use App\CentreUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class CentreUserPolicy
{
    use HandlesAuthorization;

    // Permission to export all things
    public function export(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }
}

<?php

namespace App\Policies\Store;

use App\Centre;
use App\CentreUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class CentrePolicy
{
    use HandlesAuthorization;

    // Can view the relevant centre...
    public function viewRelevantCentre(CentreUser $user, Centre $centre): bool
    {
        // ...because it's ours, or a neighbour
        return $user->isRelevantCentre($centre);
    }
}

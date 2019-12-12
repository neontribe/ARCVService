<?php

namespace App\Policies\Store;

use App\Centre;
use App\CentreUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class CentrePolicy
{
    use HandlesAuthorization;

    // permission to export only relevant things.
    public function exportFromRelevantCentre(CentreUser $user, Centre $centre)
    {
        $canDownload = ($user->downloader) ?? false;
        $canAccess = $user->isRelevantCentre($centre);
        return ($canAccess && $canDownload);
    }
}

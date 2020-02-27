<?php

namespace App\Policies\Store;

use App\CentreUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class CentreUserPolicy
{
    use HandlesAuthorization;

    // Permission to export all things
    // TODO: This is really something like "administer"; clarify
    public function export(CentreUser $user)
    {
        return ($user->role == "foodmatters_user");
    }

    // Permission to download things.
    public function download(CentreUser $user)
    {
        return ($user->downloader) ?? false;
    }
}

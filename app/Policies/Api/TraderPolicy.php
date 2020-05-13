<?php

namespace App\Policies\Api;

use App\User;
use App\Trader;
use Illuminate\Auth\Access\HandlesAuthorization;

class TraderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the api user can view the trader.
     *
     * @param  \App\User  $user
     * @param  \App\Trader  $trader
     * @return mixed
     */
    public function view(User $user, Trader $trader)
    {
        // Only if the Trader belongs to the user.
        return $user->hasTrader($trader);
    }
}

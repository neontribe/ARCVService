<?php

namespace App\Policies\Api;

use App\Trader;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TraderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the api user can view the trader.
     *
     * @param User $user
     * @param Trader $trader
     * @return bool
     */
    public function view(User $user, Trader $trader)
    {
        // Only if the Trader belongs to the user.
        return $user->hasEnabledTrader($trader);
    }
}

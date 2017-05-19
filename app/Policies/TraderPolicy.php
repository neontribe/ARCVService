<?php

namespace App\Policies;

use App\User;
use App\Trader;
use Illuminate\Auth\Access\HandlesAuthorization;

class TraderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the trader.
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

    /**
     * Determine whether the user can create traders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
    }

    /**
     * Determine whether the user can update the trader.
     *
     * @param  \App\User  $user
     * @param  \App\Trader  $trader
     * @return mixed
     */
    public function update(User $user, Trader $trader)
    {
        //
    }

    /**
     * Determine whether the user can delete the trader.
     *
     * @param  \App\User  $user
     * @param  \App\Trader  $trader
     * @return mixed
     */
    public function delete(User $user, Trader $trader)
    {
        //
    }
}

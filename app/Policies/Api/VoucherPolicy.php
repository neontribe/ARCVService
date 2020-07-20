<?php

namespace App\Policies\Api;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\Trader;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can collect the voucher.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function collect(User $user)
    {
        $trader = Trader::findOrFail(request()->trader_id);
        return $user->hasTrader($trader);
    }
}

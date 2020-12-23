<?php

namespace App\Policies\Api;

use App\Trader;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can collect the voucher.
     *
     * @param User $user
     * @return bool
     */
    public function collect(User $user)
    {
        $trader = Trader::findOrFail(request()->trader_id);
        return $user->hasEnabledTrader($trader);
    }
}

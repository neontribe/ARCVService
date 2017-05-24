<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;
use App\User;
use App\Voucher;
use App\Trader;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the voucher.
     *
     * @param  \App\User  $user
     * @param  \App\Voucher  $voucher
     * @return mixed
     */
    public function view(User $user, Voucher $voucher)
    {
        //
    }

    /**
     * Determine whether the user can create vouchers.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
       //
    }

    /**
     * Determine whether the user can update the voucher.
     *
     * @param  \App\User  $user
     * @param  \App\Voucher  $voucher
     * @return mixed
     */
    public function update(User $user, Voucher $voucher)
    {
        //
    }

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

    /**
     * Determine whether the user can delete the voucher.
     *
     * @param  \App\User  $user
     * @param  \App\Voucher  $voucher
     * @return mixed
     */
    public function delete(User $user, Voucher $voucher)
    {
        //
    }

}

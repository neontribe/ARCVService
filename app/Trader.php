<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trader extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'pic_url',
        'market_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Get the vouchers belonging to this trader.
     *
     * @return App\Voucher collection
     */
    public function vouchers()
    {
        return $this->hasMany('App\Voucher');
    }

    /**
     * Vouchers that have been submitted for payment on behalf of this trader.
     * They will have currentstate: payment_pending or reimbursed.
     *
     * @return Collection App\Voucher
     */
    public function vouchersConfirmed()
    {
        return $this->vouchers()->confirmed();
    }

    /**
     * Vouchers submitted by this trader that have a given status.
     *
     * @param String $status
     *
     * @return Collection App\Voucher
     */
    public function vouchersWithStatus($status = null)
    {
        if (empty($status)) {
            // Get all the trader's assigned vouchers
            $vouchers = $this->vouchers->all();
        } else {
            // Get the vouchers with given status, mapped to these states.
            switch ($status) {
                case "unpaid":
                    $stateCondition = "reimbursed";
                    break;
                case "unconfirmed":
                    $stateCondition = "payment_pending";
                    break;
                default:
                    $stateCondition = null;
                    break;
            }
            $statedVoucherIDs = DB::table('vouchers')
                ->leftJoin('voucher_states', 'vouchers.id', '=', 'voucher_states.voucher_id')
                ->leftJoin('traders', 'vouchers.trader_id', '=', 'traders.id')
                ->where('voucher_states.to', $stateCondition)
                ->pluck('vouchers.id')->toArray();
            // subtract them from the collected ones
            $vouchers = $this->vouchers->whereNotIn('id', $statedVoucherIDs);
        }

        return $vouchers;

    }

}

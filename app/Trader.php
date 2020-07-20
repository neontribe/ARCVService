<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'market'
    ];

    /**
     * Get the vouchers belonging to this trader.
     *
     * @return Voucher collection
     */
    public function vouchers()
    {
        return $this->hasMany('App\Voucher');
    }

    /**
     * Get the market this trader belongs to.
     *
     * @return Market
     */
    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    /**
     * Vouchers that have been submitted for payment on behalf of this trader.
     * They will have currentstate: payment_pending or reimbursed.
     *
     * @return Collection
     */
    public function vouchersConfirmed()
    {
        return $this->vouchers()->confirmed();
    }

    /**
     * Vouchers submitted by this trader that have a given status.
     * @param null $status
     * @return Collection
     */
    public function vouchersWithStatus($status = null)
    {
        /** @var HasMany $q */
        // Reminder; $q is going to be a builder object.
        $q = $this->vouchers();

        if (!empty($status)) {
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
            // Get all the trader's vouchers that have a state record of $stateCondition
            // Narrow down results to only distinct Ids
            $statedVoucherIDs = DB::table('vouchers')->select('vouchers.id')->distinct()
                ->leftJoin('voucher_states', 'vouchers.id', '=', 'voucher_states.voucher_id')
                ->where('vouchers.trader_id', '=', $this->id)
                ->where('voucher_states.to', $stateCondition)
                ->pluck('id')->toArray();

            // subtract them from the collected ones
            $q = $q->whereNotIn('id', $statedVoucherIDs);
        }

        // Finally, return our vouchers.
        return $q->orderBy('updated_at', 'desc')->get();
    }
}

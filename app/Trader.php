<?php

namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Trader extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'disabled_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'pic_url',
        'market_id',
        'disabled_at'
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
        'market',
    ];

    /**
     * Get the market this trader belongs to.
     *
     * @return BelongsTo
     */
    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Get the users this trader employs
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Disable the Trader
     */
    public function disable()
    {
        $this->disabled_at = Carbon::now();
        $this->save();
    }

    /**
     * Disable the Trader
     */
    public function enable()
    {
        $this->disabled_at = null;
        $this->save();
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
     * Get the vouchers belonging to this trader.
     *
     * @return HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Vouchers submitted by this trader that have a given status.
     * @param null $status
     * @return Collection
     */
    public function vouchersWithStatus($status = null)
    {
        $q = DB::table('vouchers')->select('*')
            ->where('trader_id', $this->id)
            ->where ('updated_at', '>=', Carbon::now()->subMonths(6))
            ->orderBy('updated_at', 'desc');

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

            if ($stateCondition) {
                $statedVoucherQuery = DB::table('vouchers')
                    ->select('vouchers.id')
                    ->distinct()
                    ->leftJoin('voucher_states', 'vouchers.id', '=', 'voucher_states.voucher_id')
                    ->where('vouchers.trader_id', $this->id)
                    ->where('vouchers.updated_at', '>=', Carbon::now()->subMonths(6))
                    ->where('voucher_states.created_at', '>=', Carbon::now()->subMonths(6))
                    ->where('voucher_states.to', $stateCondition);

                $q = $q->leftJoinSub($statedVoucherQuery, 'stated_vouchers', function ($join) {
                    $join->on('vouchers.id', '=', 'stated_vouchers.id');
                })->whereNull('stated_vouchers.id');
            }
        }
        return $q->get();
    }
}

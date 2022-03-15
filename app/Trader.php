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
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Get the users this trader employs
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Disable the Trader
     * @return void
     */
    public function disable(): void
    {
        $this->disabled_at = Carbon::now();
        $this->save();
    }

    /**
     * Disable the Trader
     * @return void
     */
    public function enable(): void
    {
        $this->disabled_at = null;
        $this->save();
    }

    /**
     * Vouchers that have been submitted for payment on behalf of this trader.
     * They will have currentstate: payment_pending or reimbursed.
     *
     * @return mixed
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
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Vouchers submitted by this trader that have a given status.
     * @param $status
     * @param array $columns
     * @return array|Collection
     */
    public function vouchersWithStatus($status = null, array $columns = ['*'])
    {
        if (!empty($status)) {
            // Get the vouchers with given status, mapped to these states.
            switch ($status) {
                case "unpaid":
                    $stateCondition = "payment_pending";
                    break;
                case "unconfirmed":
                    $stateCondition = "recorded";
                    break;
                default:
                    $stateCondition = null;
                    break;
            }

            // get the cutoff for 6 calendar months of data.
            $cutOff = Carbon::today()->subMonths(6)->startOfMonth()->format('Y-m-d H:i:s');
            $query =  "select " . implode(',', $columns) . " from (
                select vouchers.id,
                        vouchers.code,
                        vouchers.updated_at,
                        vouchers.currentstate,
                        (
                        select `to`
                            from voucher_states
                            where voucher_states.voucher_id = vouchers.id
                            and voucher_states.created_at >= '$cutOff'
                            order by updated_at desc
                            limit 1
                        ) as state
                            from vouchers
                            where trader_id = '$this->id'
                    ) as v
                where v.state = '$stateCondition'
                order by id desc, updated_at desc;"
            ;

            $q = DB::select(DB::raw($query));
        } else {
            $q = DB::table('vouchers')->select($columns)
                ->where('trader_id', $this->id)
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        return $q;
    }
}

<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        'disabled_at',
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
            $trader_id = $this->id;

            $q = DB::table(
                static function ($query) use ($cutOff, $trader_id) {
                    $query->select([
                        'vouchers.id',
                        'vouchers.code',
                        'vouchers.updated_at',
                        'vouchers.currentstate',
                    ])->addSelect([
                        'state' => VoucherState::select('to')
                            ->whereColumn('voucher_states.voucher_id', 'vouchers.id')
                            ->where('voucher_states.created_at', '>=', $cutOff)
                            ->orderByDesc('voucher_states.updated_at')
                            ->limit(1),
                    ])->from('vouchers')
                        ->where('vouchers.trader_id', $trader_id);
                },
                'innerQuery'
            )->select($columns)
                ->where('innerQuery.state', $stateCondition)
                ->orderByDesc('id')
                ->orderByDesc('updated_at')
                ->get();
        } else {
            // get all the vouchers
            $q = DB::table('vouchers')
                ->select($columns)
                ->where('trader_id', $this->id)
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        return $q;
    }
}

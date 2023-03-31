<?php

namespace App;

use Carbon\Carbon;
use DateTimeInterface;
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
     * @return Collection
     */
    public function vouchersWithStatus($status = null, array $columns = ['*']): Collection
    {
        // get all the vouchers
        $q = DB::table('vouchers')->select($columns)->where('trader_id', $this->id);

        if (!empty($status)) {
            // Get the vouchers with given status
            $stateCondition = match ($status) {
                "unpaid" => "payment_pending",
                "unconfirmed" => "recorded",
                default => null,
            };

            // get the cutoff for 6 calendar months of data.
            $cutOff = Carbon::today()->subMonths(6)->startOfMonth()->format('Y-m-d H:i:s');

            $q->where('currentstate', $stateCondition)->where('updated_at', '>=', $cutOff);
        }
        return $q->orderByDesc('updated_at')->get();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $dispatched_at
 * @property Centre $centre
 * @property Voucher[] $vouchers
 */
class Delivery extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'range',
        'centre_id',
        'dispatched_at'
    ];

    /**
     * The attributes that are cast as dates.
     *
     * @var array
     */
    protected $casts = [
        'dispatched_at' => 'datetime',
    ];

    /**
     * @return HasMany
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return BelongsTo
     */
    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Call a macro [see AppServiceProvider::boot()] to add an order by centre name
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByCentre(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy(
            Centre::select('name')
                ->whereColumn('centres.id', 'deliveries.centre_id'),
            $direction
        );
    }

    /**
     * Scope order by range string (lazy)
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByRange(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy('range', $direction);
    }

    /**
     * Scope order by dispatch date
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByDispatchDate(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy('dispatched_at', $direction);
    }

    /**
     * Strategy to sort columns
     *
     * @param Builder $query
     * @param array $sort
     */
    public function scopeOrderByField(Builder $query, array $sort): void
    {
        $direction = $sort['direction'] ?? 'asc';
        switch ($sort['orderBy']) {
            case 'centre':
                $query->orderByCentre($direction);
                break;
            case 'range':
                $query->orderByRange($direction);
                break;
            case 'dispatchDate':
                $query->orderByDispatchDate($direction);
                break;
            default:
                // default to date order ascending, so new things are on the BOTTOM.
                $query->orderByDispatchDate('asc');
                break;
        }
    }
}

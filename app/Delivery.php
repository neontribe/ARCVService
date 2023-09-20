<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $dispatched_at
 * @property Centre $centre
 */
class Delivery extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'range', 'centre_id', 'dispatched_at'
    ];

    /**
     * The attributes that are cast as dates.
     *
     * @var array
     */
    protected $dates = [
        'dispatched_at',
    ];

    /**
     * @return HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return BelongsTo
     */
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Call a macro [see AppServiceProvider::boot()] to add an order by centre name
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByCentre(Builder $query, $direction = 'asc')
    {
        $query->orderBySub(
            Centre::select('name')
                ->whereRaw('deliveries.centre_id = centres.id'),
            $direction
        );
    }

    /**
     * Scope order by range string (lazy)
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByRange(Builder $query, $direction = 'asc')
    {
        $query->orderBy('range', $direction);
    }

    /**
     * Scope order by dispatch date
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByDispatchDate(Builder $query, $direction = 'asc')
    {
        $query->orderBy('dispatched_at', $direction);
    }

    /**
     * Strategy to sort columns
     *
     * @param Builder $query
     * @param array $sort
     * @return Builder
     */
    public function scopeOrderByField(Builder $query, array $sort)
    {
        switch ($sort['orderBy']) {
            case 'centre':
                $query->orderByCentre($sort['direction']);
                break;
            case 'range':
                $query->orderByRange($sort['direction']);
                break;
            case 'dispatchDate':
                $query->orderByDispatchDate($sort['direction']);
                break;
            default:
                // default to date order ascending, so new things are on the BOTTOM.
                $query->orderByDispatchDate('asc');
                break;

        }
        return $query;
    }
}

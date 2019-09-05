<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Call a Macro to add an orderBy to the system
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

    public function scopeOrderByRange(Builder $query, $direction = 'asc')
    {
        $query->orderBy('range', $direction);
    }

    public function scopeOrderByDispatchDate(Builder $query, $direction = 'asc')
    {
        $query->orderBy('dispatched_at', $direction);
    }

    /**
     * Strategy to sort columns
     * @param Builder $query
     * @param array $sort
     * @return Builder
     */
    public function scopeOrderByField(Builder $query, $sort)
    {
        switch ($sort['orderBy']) {
            case 'centre':
                return $query->orderByCentre($sort['direction']);
                break;
            case 'range':
                return $query->orderByRange($sort['direction']);
                break;
            case 'dispatchDate':
                return $query->orderByDispatchDate($sort['direction']);
                break;
            default:
                // do nothing
        }
    }
}

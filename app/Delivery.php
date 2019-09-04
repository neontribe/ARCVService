<?php

namespace App;

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
        'dispatched_on',
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
}

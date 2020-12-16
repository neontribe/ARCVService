<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Market extends Model
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
        'location',
        'sponsor_id',
        'payment_message',
    ];

    protected $rules = [
        'payment_message' => [
            'required',
        ],
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sponsor_id' => 'int',
    ];

    /**
     * The attributes to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'sponsor_shortcode',
    ];

    /**
     * Get the sponsor this market belongs to.
     *
     * @return BelongsTo
     */
    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * Get the traders this market has.
     *
     * @return HasMany
     */
    public function traders()
    {
        return $this->hasMany(Trader::class);
    }

    /**
     * Get the sponsor shortcode.
     *
     * @return string
     */
    public function getSponsorShortcodeAttribute()
    {
        return $this->sponsor->shortcode;
    }

    public function getPaymentMessageAttribute($value)
    {
        return trans('api.messages.voucher_payment_requested', ['payment_request_message' => $value]);
    }
}

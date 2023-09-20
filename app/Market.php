<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property Sponsor $sponsor
 */
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

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property string $location
 * @property string $payment_message
 * @property Sponsor $sponsor
 * @property Trader[] $traders
 */
class Market extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'location',
        'sponsor_id',
        'centre_id',
        'payment_message',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sponsor_id' => 'int',
        'deleted_at' => 'datetime',
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
     * If this is an "internal market" it will have a centre
     */
    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }


    /**
     * Get the sponsor this market belongs to.
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * Get the traders this market has.
     */
    public function traders(): HasMany
    {
        return $this->hasMany(Trader::class);
    }

    /**
     * Scope to markets that have at least one trader — i.e. are open for business.
     */
    public function scopeTrading(Builder $query): Builder
    {
        return $query->has('traders');
    }

    /**
     * Get the sponsor shortcode.
     */
    public function getSponsorShortcodeAttribute(): ?string
    {
        return $this->sponsor?->shortcode;
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Is it internal?
     */
    public function isInternal(): bool
    {
        return $this->centre_id !== null;
    }
}

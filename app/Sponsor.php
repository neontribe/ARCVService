<?php

namespace App;

use DateTimeInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Eloquent
 * @property string $name
 * @property string $shortcode
 * @property bool $can_tap
 * @property int $programme
 * @property Voucher[] $vouchers
 * @property Centre[] $centres
 * @property Market[] $markets
 * @property Evaluation[] $evaluations
 */
class Sponsor extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'shortcode',
        'can_tap',
        'programme',
    ];

    protected $appends = [
        'programme_name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'can_tap' => 'boolean',
        'programme' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Gets an english version of the programme name
     *
     * @return mixed
     */
    public function getProgrammeNameAttribute()
    {
        return config('arc.programmes')[$this->programme];
    }

    /**
     * Get the vouchers associated with this Sponsor.
     *
     * @return HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Get the Sponsor's Centres
     *
     * @return HasMany
     */
    public function centres()
    {
        return $this->hasMany(Centre::class);
    }

    /**
     * Get the Sponsors Markets
     *
     * @return HasMany
     */
    public function markets()
    {
        return $this->hasMany(Market::class);
    }

    /**
     * Get this list of evaluations that this sponsor uses
     *
     * @return HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
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

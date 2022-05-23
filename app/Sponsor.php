<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sponsor extends Model
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
}

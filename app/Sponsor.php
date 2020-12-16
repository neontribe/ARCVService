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
        'can_tap'
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
    ];

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
        return $this->hasMany('App\Centre');
    }

    /**
     * Get this list of evaluations that this sponsor uses
     *
     * @return HasMany
     */
    public function evaluations()
    {
        return $this->hasMany('App\Evaluation');
    }
}

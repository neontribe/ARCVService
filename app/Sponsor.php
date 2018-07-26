<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Get the vouchers associated with this Sponsor.
     *
     * @return App\Voucher Collection
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Get the Sponsor's Centres
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function centres()
    {
        return $this->hasMany('App\Centre');
    }
}

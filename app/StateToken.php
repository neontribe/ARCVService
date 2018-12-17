<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class StateToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The vouchers that share this StateToken
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucherSates()
    {
        return $this->hasMany(VoucherState::class);
    }
}

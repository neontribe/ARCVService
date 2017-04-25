<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sponsor_id',
        'assignee_id',
        'redeemer_id',
        'creditor_id',
        'code',
        'state'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function redeemer()
    {
        return $this->belongsTo(User::class);
    }

    public function creditor()
    {
        return $this->belongsTo(Trader::class);
    }

    public function events()
    {
        return $this->hasMany(VoucherEvents::class);
    }

    public function store()
    {
        // Todo: MOAR MEAT
        // tis is for updating the


    }
}

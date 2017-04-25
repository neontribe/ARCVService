<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
// hard deletes on these; if only because we'll data-warehouse them at some point.


class VoucherEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

}

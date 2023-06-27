<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "url",
        "status",
        "created",
        "data",
        "hash",
        "trader_id",
    ];

    /**
     * The attributes that should be case to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'int',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Rules for validation.
     *
     * @var array
     */
    public static $rules = [
        'status' => ['numeric'],
    ];
}

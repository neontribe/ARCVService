<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected $rules = [
        'payment_message' => [
            'required',
        ],
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
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'sponsor',
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
     * Get the sponsor belonging to this market.
     *
     * @return \App\Sponsor
     */
    public function sponsor()
    {
        return $this->belongsTo('App\Sponsor');
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
}

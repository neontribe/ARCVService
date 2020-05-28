<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    // An evaluation is currently for a sponsor

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'value',
        'purpose',
        'entity',
        'sponsor_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sponsor()
    {
        // Oddly saveMany was giving issues until this relationship was two way.
        // Not entirely sure why, but this fixed it.
        // TODO: review and amend as necessary
        return $this->belongsTo('App\Sponsor');
    }
}

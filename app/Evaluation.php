<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 * @property string $name
 * @property string $value
 * @property string $purpose
 * @property Sponsor $sponsor
 */
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

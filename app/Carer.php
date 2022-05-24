<?php

namespace App;

use App\Traits\Aliasable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carer extends Model
{
    use Aliasable;

    /**
     * Trait overrides for names
     * @var string[]
     */
    public $programmeAliases = [
        "Carer",
        "Participant",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get the Family this Carer picks up for.
     *
     * @return BelongsTo
     */
    public function family() : BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}

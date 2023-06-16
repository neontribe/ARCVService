<?php

namespace App;

use App\Traits\Aliasable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carer extends Model
{
    use Aliasable;
    use SoftDeletes;

    public const PROGRAMME_ALIASES = [
        "Child",
        "Participant",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
		'ethnicity',
		'language',
    ];

	/**
	 * The attributes to append to the model's array form.
	 *
	 * @var array
	 */
	protected $appends = [
		'ethnicity',
		'language',
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

    /**
     * Overrides the delete function to alter the name
     */
    public function delete()
    {
         $this->name = 'Deleted';
         $this->save();
         return parent::delete();
    }
}

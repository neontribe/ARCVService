<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Eloquent
 * @property string $name
 * @property string $prefix
 * @property string $print_pref
 * @property Sponsor $sponsor
 * @property Registration[] $registrations
 * @property CentreUser[] $centreUsers
 * @property Centre[] $neighbours
 * @property Family[] $families
 */
class Centre extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'prefix', 'print_pref', 'sponsor_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function nextCentreSequence(): int
    {
        // Get the last family
        $last_family = $this->families()->orderByDesc('centre_sequence')->first();

        // Set a default
        $sequence = 1;

        // Override it if the family has a sequence.
        if ($last_family && $last_family->centre_sequence) {
            $sequence = $last_family->centre_sequence + 1;
        }

        return $sequence;
    }

    /**
     * Get the Registrations for this Centre
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the CentreUsers who belong to this Centre
     */
    public function centreUsers(): BelongsToMany
    {
        return $this->belongsToMany(CentreUser::class);
    }

    /**
     * Get the Sponsor for this Centre
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * Gets all the siblings under the same parent (including this one).
     * Self join; possible a better way to do this.
     */
    public function neighbours(): HasMany
    {
        return $this->hasMany(related: 'App\Centre', foreignKey: 'sponsor_id', localKey: 'sponsor_id');
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class, 'initial_centre_id');
    }
}

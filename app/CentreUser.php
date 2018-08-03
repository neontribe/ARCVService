<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CentreUser extends Authenticatable
{
    use Notifiable;

    protected $guard = 'store';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the Notes that belong to this CentreUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the CentreUser's Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function centre()
    {
        return $this->belongsTo('App\Centre');
    }

    /**
     * Get the relevant centres for this CentreUser, accounting for it's role
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|static[]
     */
    public function relevantCentres()
    {
        // default to empty collection
        $centres = collect();
        switch ($this->role) {
            case "foodmatters_user":
                // Just get all centres
                $centres = Centre::all();
                break;
            case "centre_user":
                // If we have one, get our centre's neighbors
                if (!is_null($this->centre)) {
                    $centres = $this->centre->neighbors;
                }
                break;
        }
        return $centres;
    }

    /**
     * Is a given centre relevant to this CentreUser?
     *
     * @param Centre $centre
     * @return bool
     */
    public function isRelevantCentre(Centre $centre)
    {
        return $this->relevantCentres()->contains('id', $centre->id);
    }
}

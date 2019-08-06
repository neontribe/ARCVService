<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\StorePasswordResetNotification;

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
     * Calculated attributes
     *
     * @var array
     */
    protected $appends = [
        'homeCentre'
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
    //Because of merge and refactoring User to CentreUser, FK has to be explicitly stated here
    public function notes()
    {
        return $this->hasMany('App\Note', 'user_id');
    }

    /**
     * Get the CentreUser's Current Centre
     *
     * @return Centre
     */
    public function getCentreAttribute()
    {
        // Check the session for a variable.
        $currentCentreId = session('CentreUserCurrentCentreId');

        // check it's a number
        if (is_numeric($currentCentreId)) {
            // check the centre is in our set
            /** @var Centre $currentCentre */
            $currentCentre = $this->centres()->where('id', $currentCentreId)->first();
            if ($currentCentre) {
                return $currentCentre;
            }
        }

        // return default homeCentre if broken.
        /** @var Centre $currentCentre */
        $currentCentre = $this->homeCentre;
        return $currentCentre;
    }

    /**
     * Get the centres assigned to a user
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function centres()
    {
        return $this->belongsToMany('App\Centre');
    }

    /**
     * Gets the first homeCentre, makes it an attribute.
     *
     * @return Centre
     */
    public function getHomeCentreAttribute()
    {
        return $this->homeCentres()->first();
    }

    /**
     * Get the home centres for this user
     * Alas, we lack a belongsToThrough method to this is a collections.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    protected function homeCentres()
    {
        return $this->belongsToMany('App\Centre')->wherePivot('homeCentre', true);
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
                // If we have one, get our centre's neighbours
                if (!is_null($this->centre)) {
                    $centres = $this->centre->neighbours;
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

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new StorePasswordResetNotification($token, $this->name));
    }
}

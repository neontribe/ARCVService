<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use App\Notifications\StorePasswordResetNotification;

class CentreUser extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guard = 'store';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'downloader',
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'downloader' => 'boolean',
    ];

    /**
     * Get the Notes that belong to this CentreUser
     *
     * @return HasMany
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
     * @return belongsToMany
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
     * @return belongsToMany
     */
    protected function homeCentres()
    {
        return $this->belongsToMany('App\Centre')->wherePivot('homeCentre', true);
    }

    /**
     * Get the relevant centres for this CentreUser, accounting for it's role
     *
     * @return Collection
     */
    public function relevantCentres($programme = 0)
    {
        // default to empty collection
        $centres = collect([]);
        switch ($this->role) {
            case "foodmatters_user":
                $centres = collect(Centre::get()->all());
                $centres = $centres->filter(function($model) use ($programme) {
                    return $model->sponsor->programme === $programme;
                });
                break;
            case "centre_user":
                // If we have one, get our centre's neighbours
                /** @var Centre $centre */
                $centre = $this->centre;
                if (!is_null($centre)) {
                    $centres = collect($centre->neighbours()->get()->all());
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

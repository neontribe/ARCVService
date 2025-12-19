<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use App\Notifications\StorePasswordResetNotification;

/**
 * @mixin Eloquent
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property bool $downloader
 * @property Note[] $notes
 * @property Centre[] $centres
 * @property Centre[] $homeCentres
 */
class CentreUser extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected string $guard = 'store';

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
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the Notes that belong to this CentreUser
     */
    //Because of merge and refactoring User to CentreUser, FK has to be explicitly stated here
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'user_id');
    }

    /**
     * Get the CentreUser's Current Centre
     */
    public function getCentreAttribute(): Centre
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
     */
    public function centres(): BelongsToMany
    {
        return $this->belongsToMany(Centre::class);
    }

    /**
     * Gets the first homeCentre, makes it an attribute.
     */
    public function getHomeCentreAttribute(): ?Model
    {
        return $this->homeCentres()->first();
    }

    /**
     * Get the home centres for this user
     * Alas, we lack a belongsToThrough method to this is a collections.
     */
    protected function homeCentres(): BelongsToMany
    {
        return $this->belongsToMany(Centre::class)->wherePivot('homeCentre', true);
    }

    /**
     * Get the relevant centres for this CentreUser, accounting for it's role
     */
    public function relevantCentres($programme = 0): Collection
    {
        return match ($this->role) {
            'foodmatters_user' => Centre::whereHas('sponsor', static function ($query) use ($programme) {
                $query->where('programme', $programme);
            })->get(),

            'centre_user' => $this->centre?->neighbours()->get() ?? collect(),

            default => collect(),
        };
    }

    /**
     * Is a given centre relevant to this CentreUser?
     */
    public function isRelevantCentre(Centre $centre): bool
    {
        return $this->relevantCentres()->contains('id', $centre->id);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StorePasswordResetNotification($token, $this->name));
    }
}

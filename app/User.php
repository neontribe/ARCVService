<?php

namespace App;

use App\Notifications\ApiPasswordResetNotification;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Check if the trader belongs to the user.
     *
     * @param Trader $trader
     * @return boolean
     */
    public function hasEnabledTrader(Trader $trader)
    {
        return $this->traders()
            ->where('id', $trader->id)
            ->whereNull('disabled_at')
            ->exists();
    }

    /**
     * Get the user's traders.
     *
     * @return BelongsToMany
     */
    public function traders()
    {
        return $this->belongsToMany(Trader::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ApiPasswordResetNotification($token, $this->name));
    }
}

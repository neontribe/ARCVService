<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use App\Notifications\ApiPasswordResetNotification;

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
        'name', 'email', 'password',
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
     * Get the user's traders.
     *
     * @return Trader Collection.
     */
    public function traders()
    {
        return $this->belongsToMany(Trader::class);
    }

    /**
     * Check if the trader belongs to the user.
     *
     * @param Trader $trader
     *
     * @return boolean
     */
    public function hasTrader($trader)
    {
        return in_array($trader->id, $this->traders()->pluck('id')->toArray());
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ApiPasswordResetNotification($token, $this->name));
    }
}

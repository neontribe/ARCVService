<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class StateToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * StateToken constructor. Always end up with a UUID
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // Intercept and make a Uuid
        $attributes["uuid"] = empty($attributes["uuid"])
            ? self::generateUnusedToken()
            : $attributes["uuid"]
        ;

        // Send that up the chain.
        parent::__construct($attributes = []);
    }

    public static function generateUnusedToken()
    {
        do {
            // TODO: Deal with possibility uuid4() may throw an exception of it's own?
            // TODO: Also, potential infinite loop; i mean, it *shouldn't* happen, right...?
            // We could throw an exception if iterations > 10, and handle elsewhere
            $candidate = Uuid::uuid4()->toString();
        } while (self::where('uuid', '=', $candidate)->exists());
        return $candidate;
    }

    /**
     * The vouchers that share this StateToken
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucherSates()
    {
        return $this->hasMany(VoucherState::class);
    }
}

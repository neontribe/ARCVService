<?php

namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Log;

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
     * Makes and checks for an unused token
     *
     * @return string
    */
    public static function generateUnusedToken()
    {
        do {
            // TODO: Deal with possibility uuid4() may throw an exception of it's own?
            try {
                $candidate = Uuid::uuid4()->toString();
            } catch (\Exception $e) {
                // Uuid4() throws exceptions, apparently! Log that and die, I guess?
                Log::warning($e->getMessage());
                abort(500, $e->getMessage());
            }
            // Check if it's in use.
            $usedToken = self::isUsedToken($candidate);
        } while ($usedToken === true);

        return $candidate;
    }

    /**
     * Tidies Tokens of a certain age.
     * @param int $age
     * @return bool|null
     */
    public static function tidy($age = 30)
    {
        $expireDate = Carbon::today()->subDays($age)->format('Y-m-d H:i:s');
        return self::where('created_at', '<', $expireDate)->delete();
    }

    /**
     * Checks a UUID has been used
     * @param $candidate
     * @return bool
     */
    public static function isUsedToken($candidate)
    {
        $tableName = 'state_tokens';
        return DB::table($tableName)
            ->where('uuid', $candidate)
            ->exists();
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

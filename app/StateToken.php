<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramsey\Uuid\Uuid;
use Log;
/**
 * @property VoucherState $voucherStates
 * @property User $user
 * @property AdminUser $adminUser
 */
class StateToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'admin_user_id',
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
    public function voucherStates()
    {
        return $this->hasMany(VoucherState::class);
    }

    /**
     * The user that created this StateToken
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin user that updated this StateToken
     *
     * @return BelongsTo
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}

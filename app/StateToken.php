<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property int|null $user_id
 * @property int|null $admin_user_id
 * @property VoucherState $voucherStates
 * @property User $user
 * @property AdminUser $adminUser
 */
class StateToken extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'admin_user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'admin_user_id' => 'integer',
    ];

    /**
     * Generate a unique, unused UUID token.
     */
    public static function generateUnusedToken(): string
    {
        do {
            $candidate = Str::uuid()->toString();
        } while (static::isUsedToken($candidate));

        return $candidate;
    }

    /**
     * Check whether a UUID token is already in use.
     */
    public static function isUsedToken(string $candidate): bool
    {
        return static::where('uuid', $candidate)->exists();
    }

    /**
     * The voucher states that share this StateToken.
     */
    public function voucherStates(): HasMany
    {
        return $this->hasMany(VoucherState::class);
    }

    /**
     * The user that created this StateToken.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin user associated with this StateToken.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}

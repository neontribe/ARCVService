<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
     * Lightweight check for outstanding payments to highlight in dashboard
     */
    public static function checkIfOutstandingPayments(): bool
    {
        return self::pending()
            ->withinPaymentWindow()
            ->exists();
    }

    /**
     * Constrains results to the payment window.
     */
    public function scopeWithinPaymentWindow(Builder $query, ?Carbon $date = null): void
    {
        $from = $date ?? Carbon::now()
            ->startOfDay()
            ->subDays(config('arc.payment_window_days'))
        ;

        $query->where('created_at', '>=', $from);
    }

    /**
     * Constrains to payment requests not yet actioned by an admin.
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('admin_user_id');
    }

    /**
     * Constrains to payment requests already actioned by an admin.
     */
    public function scopeReimbursed(Builder $query): void
    {
        $query->whereNotNull('admin_user_id');
    }

    /**
     * Eager-loads all relationships required to render the payments view.
     * Kept as a scope so callers don't have to know or repeat the tree.
     */
    public function scopeWithPaymentRelations(Builder $query): void
    {
        $query->with([
            'user',
            'voucherStates.voucher.trader.market.sponsor',
            'voucherStates.voucher.sponsor',
        ]);
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

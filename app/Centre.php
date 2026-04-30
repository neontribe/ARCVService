<?php

namespace App;

use App\Observers\CentreObserver;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * @mixin Eloquent
 * @property string $name
 * @property string $prefix
 * @property string $print_pref
 * @property boolean $can_collect
 * @property Sponsor $sponsor
 * @property Registration[] $registrations
 * @property CentreUser[] $centreUsers
 * @property Centre[] $neighbours
 * @property Family[] $families
 */

#[ObservedBy(CentreObserver::class)]
class Centre extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'prefix',
        'print_pref',
        'sponsor_id',
        'can_collect',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Casts
     *
     * @var array
     */
    protected $casts = [
        'can_collect' => 'boolean',
    ];

    public function nextCentreSequence(): int
    {
        // Get the last family
        $last_family = $this->families()->orderByDesc('centre_sequence')->first();

        // Set a default
        $sequence = 1;

        // Override it if the family has a sequence.
        if ($last_family && $last_family->centre_sequence) {
            $sequence = $last_family->centre_sequence + 1;
        }

        return $sequence;
    }

    /**
     * All internal markets for this centre.
     * Use the open() scope to restrict to those with at least one trader.
     */
    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }

    /**
     * Get the Registrations for this Centre
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the CentreUsers who belong to this Centre
     */
    public function centreUsers(): BelongsToMany
    {
        return $this->belongsToMany(CentreUser::class);
    }

    /**
     * Get the Sponsor for this Centre
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * Gets all the siblings under the same parent (including this one).
     * Self join; possible a better way to do this.
     */
    public function neighbours(): HasMany
    {
        return $this->hasMany(related: __CLASS__, foreignKey: 'sponsor_id', localKey: 'sponsor_id');
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class, 'initial_centre_id');
    }

    /**
     * Relationship for addressing vouchers that are assigned to this centre via deliveries.
     */
    public function availableVouchers(): HasManyThrough
    {
        return $this->hasManyThrough(Voucher::class, Delivery::class)
            ->where('vouchers.currentstate', 'printed')
            ->whereNull('vouchers.bundle_id');
    }

    /**
     * How many vouchers do we have access to?
     */
    public function getPoolSize(): int
    {
        return $this->availableVouchers()->count();
    }

    /**
     * @throws Throwable
     */
    public function claimFromPool(int $quantity): Collection
    {
        return DB::transaction(function () use ($quantity) {
            $vouchers = $this->availableVouchers()
                ->orderBy('deliveries.dispatched_at')
                ->orderBy('vouchers.id')
                ->select('vouchers.*')
                ->limit($quantity)
                ->lockForUpdate()
                ->get();

            if ($vouchers->count() < $quantity) {
                throw new RuntimeException(
                    "Pool has {$vouchers->count()} vouchers available, {$quantity} requested."
                );
            }

            return $vouchers;
        });
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * @property int $entitlement
 * @property Registration $registration
 * @property Carer $collectingCarer
 * @property Centre $disbursingCentre
 * @property User $disbursingUser
 * @property Carbon|null $disbursed_at
 */
class Bundle extends Model
{
    protected $fillable = [
        'entitlement',
        'registration_id',
        'collecting_carer_id',
        'disbursed_at',
        'disbursing_centre_id',
        'disbursing_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    /**
     * Add vouchers to this bundle by code, skipping any already attached.
     */
    public function addVouchers(array $voucherCodes): array
    {
        $currentCodes = $this->vouchers->pluck('code')->all();

        // Only attempt codes not already on this bundle.
        $newCodes = array_values(array_diff($voucherCodes, $currentCodes));
        $vouchers = Voucher::whereIn('code', $newCodes)->get();

        return $this->alterVouchers($vouchers, $newCodes, $this);
    }

    /**
     * Validate and reassign a collection of vouchers to the given bundle (or null to unbundle).
     */
    public function alterVouchers(Collection $vouchers, array $codes = [], ?self $bundle = null): array
    {
        $errors = [];

        // Detect codes that have no corresponding DB row.
        $missingCodes = array_values(array_diff($codes, $vouchers->pluck('code')->all()));
        if ($missingCodes !== []) {
            $errors['codes'] = $missingCodes;
        }

        foreach ($vouchers as $voucher) {
            match (true) {
                // Already disbursed — cannot be reassigned.
                $voucher->bundle?->disbursed_at !== null => $errors['disbursed'][] = $voucher->code,

                // Belongs to a *different* bundle — must be manually removed first.
                $voucher->bundle !== null && $bundle !== null => $errors['bundled'][] = $voucher,

                // State machine forbids collecting (expired, void, recorded, payment_pending, paid).
                !$voucher->transitionAllowed('collect') => $errors['used'][] = $voucher->code,

                // All clear — reassign.
                default => $voucher->bundle()->associate($bundle)->save(),
            };
        }

        return $errors;
    }

    /**
     * Sync this bundle's vouchers to exactly the supplied set of codes.
     * Runs inside a transaction; rolls back and returns a 'transaction' error key on failure.
     */
    public function syncVouchers(array $voucherCodes): array
    {
        $errors = [];

        try {
            DB::transaction(function () use ($voucherCodes, &$errors): void {
                $currentCodes = $this->vouchers->pluck('code')->all();

                // Codes to remove from the bundle.
                $removeCodes = array_values(array_diff($currentCodes, $voucherCodes));
                $removeVouchers = $this->vouchers()->whereIn('code', $removeCodes)->get();

                $errors = array_merge_recursive(
                    $this->alterVouchers($removeVouchers, $removeCodes, null),
                    $errors,
                );

                // Codes to add.
                $errors = array_merge_recursive(
                    $this->addVouchers($voucherCodes),
                    $errors,
                );

                if ($errors !== []) {
                    throw new RuntimeException('Errors during voucher sync transaction.');
                }
            });
        } catch (Throwable $e) {
            Log::error(sprintf(
                'Bad transaction for %s@%s by service user %s',
                self::class,
                __FUNCTION__,
                Auth::id() ?? 'unauthenticated',
            ));
            Log::error($e->getTraceAsString());

            $errors['transaction'] = true;
        }

        return $errors;
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function collectingCarer(): BelongsTo
    {
        return $this->belongsTo(Carer::class);
    }

    public function disbursingCentre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function disbursingUser(): BelongsTo
    {
        return $this->belongsTo(CentreUser::class);
    }

    public function scopeDisbursed(Builder $query): Builder
    {
        return $query->whereNotNull('disbursed_at');
    }
}

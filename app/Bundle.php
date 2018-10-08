<?php

namespace App;

use DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Log;
use SM\SMException;

class Bundle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entitlement',
        'disbursed_at',
        'centre_id',
        'family_id'
    ];

    protected $rules = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];

    /**
     * These are turned into Date objects on get
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'disbursed_at'  // When it was handed out.
    ];

    /**
     * Refactored function that works out if we broke anything then adds vouchers.
     *
     * @param Collection $vouchers
     * @param array $codes
     * @param Bundle|null $bundle
     * @return array
     */
    private function alterVouchers(Collection $vouchers, array $codes, Bundle $bundle = null)
    {
        $errors = [];
        $badCodes = array_diff($vouchers->pluck("code")->toArray(), $codes);

        if (!isEmpty($badCodes)) {
            //Stop! report the bad codes!
            $errors["codes"] = (array_key_exists("badCodes", $errors))
                ? array_merge($badCodes, $errors)
                : $badCodes;
        } else {
            // Run all these.
            $vouchers->each(
                function (Voucher $voucher) use ($bundle, $errors) {
                    try {
                        $voucher->setBundle($bundle);
                    } catch (SMException $e) {
                        $errors["transitions"][] = $voucher->code;
                        // don't rethrow!
                    }
                }
            );
        }
        return $errors;
    }

    /**
     * Syncs an array of voucher codes with vouchers();
     *
     * @param array $voucherCodes array of cleaned Voucher codes
     * @return array $errors Errors
     */
    public function syncVouchers(array $voucherCodes)
    {
        $self = $this;
        $errors = [];

        // If we get an unhandled exception, we should halt.
        try {
            DB::transaction(function () use ($self, $voucherCodes, $errors) {

                $currentCodes = $this->vouchers
                    ->pluck('code')
                    ->toArray();

                // Calculate vouchers to remove.
                $unBundleCodes = array_diff($currentCodes, $voucherCodes);

                // Find the vouchers to remove.
                $removeVouchers = $this->vouchers()->whereIn('code', $unBundleCodes)->get();
                $removeErrors = $this->alterVouchers($removeVouchers, $unBundleCodes, null);
                if (!isEmpty($removeErrors)) {
                    array_merge_recursive($removeErrors, $errors);
                }

                // Calculate vouchers to add
                $enBundleCodes = array_diff($voucherCodes, $currentCodes);

                // Find vouchers to Add.
                $addVouchers = Voucher::whereIn('code', $enBundleCodes)->get();
                $addErrors = $this->alterVouchers($addVouchers, $enBundleCodes, $self);
                if (!isEmpty($addErrors)) {
                    array_merge_recursive($addErrors, $errors);
                }

                if (!isEmpty($errors)) {
                    throw new \Exception("Errors during transaction");
                };
            });
        } catch (\Throwable $e) {
            // Log it
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Add an error notification for the caller to deal with
            $errors["transaction"] = true;
        }
        return $errors;
    }

    /**
     * Return the Centre it was allocated to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Get the Registration this bundle is for
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * The vouchers in this Bundle
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }


}


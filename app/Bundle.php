<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entitlement',
        'allocated_at',
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
        'allocated_at'  // When it was handed out.
    ];

    /**
     * Syncs an array of voucher codes with vouchers();
     *
     * @param array $voucherCodes array of cleaned Voucher codes
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function syncVouchers(array $voucherCodes)
    {
        $self = $this;
        $currentCodes = $this->vouchers
            ->pluck('code')
            ->toArray();

        // Remove excess vouchers
        $unBundleCodes = array_diff($currentCodes, $voucherCodes);
        $this->vouchers()
            ->whereIn('code', $unBundleCodes)
            ->each(
                // pass null to dissociate
                function (Voucher $voucher) {
                    $voucher->setBundle(null);
                }
            );

        // Add more vouchers
        $enBundleCodes = array_diff($voucherCodes, $currentCodes);

        // detect vouchers NOT in database.
        Vouchers::whereIn('code', $enBundleCodes)
            ->each(
                // pass $this as Bundle $self
                function (Voucher $voucher) use ($self) {
                    $voucher->setBundle($self);
                }
            );

        // reload and return collection.
        return $this->vouchers->fresh();
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


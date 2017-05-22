<?php

namespace App;

use App\Traits\Statable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use Statable; // import the state transition stuff.
    use SoftDeletes; // import soft delete.
    protected $dates = ['deleted_at'];

    const HISTORY_MODEL = 'App\VoucherState'; // the related model to store the history
    const SM_CONFIG = 'Voucher'; // the SM graph to use

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sponsor_id',
        'trader_id',
        'code',
        'currentstate', // SM_CONFIG looks at this.
    ];

    /**
     * The attributes that should be case to native types.
     *
     * @var array
     */
    protected $casts = [
        'sponsor_id' => 'int',
        'trader_id' => 'int',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Rules for validation.
     *
     * @var array
     */
    public static $rules = [
        // Might need to add a 'sometimes' if any required fields can be absent from requests.
        'trader_id' => ['numeric', 'exists:traders,id'],
        // My regex might be pants... but until we get the edit form spun up who cares?
        'code' => ['required', 'unique:vouchers', 'regex:[A-Z][A-Z][A-Z][0-9]{8}'],
        // Not sure about this one. We might be able to secify config instead.
        'currentstate' => ['required', 'in_array:voucher_state,to', 'max:24'],
        'sponsor_id' => ['numeric', 'required', 'exists:sponsors,id'],
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function trader()
    {
        return $this->belongsTo(Trader::class);
    }

    /**
     * Get the most recent created_at date
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return App\VoucherState
     */
    public function paymentPendedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'payment_pending')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Get the most recent created_at date
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return App\VoucherState
     */
    public function recordedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'recorded')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Get the most recent created_at date
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return App\VoucherState
     */
    public function reimbursedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'reimbursed')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Limit Vouchers to ones that have been confirmed for payment.
     * This will include both pending and reimbersed vouchers.
     *
     * @return query $query
     */
    public function scopeConfirmed($query)
    {
        $states = ['payment_pending', 'reimbursed'];
        return $query->whereIn('currentstate', $states);
    }

    public static function findByCode($code)
    {
        return self::where('code', $code)->get()->first();
    }


    public static function findByCodes($codes)
    {
        return self::whereIn('code', $codes)->get();
    }
}

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
        // DB constraint is max chars, but is it in fact always supposed to be 8 chars?
        'code' => ['required', 'unique:vouchers', 'max:32'],
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

    public static function findByCode($code)
    {
        return self::where('code', $code)->get()->first();
    }


    public static function findByCodes($codes)
    {
        return self::whereIn('code', $codes)->get();
    }
}

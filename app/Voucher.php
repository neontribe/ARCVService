<?php

namespace App;

use App\Traits\Statable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use Statable; // import the state transition stuff.
    use SoftDeletes; // import soft delete.

    const HISTORY_MODEL = 'App\VoucherState'; // the related model to store the history
    const SM_CONFIG = 'Voucher'; // the SM graph to use

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sponsor_id',
        'assignee_id',
        'redeemer_id',
        'creditor_id',
        'code',
        'state' // SM_CONFIG looks at this.
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function redeemer()
    {
        return $this->belongsTo(User::class);
    }

    public function creditor()
    {
        return $this->belongsTo(Trader::class);
    }

}

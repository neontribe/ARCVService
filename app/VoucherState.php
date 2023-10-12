<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

// hard deletes on these; if only because we'll data-warehouse them at some point.

/**
 * @mixin Eloquent
 * @property string $transition;
 * @property string $from;
 * @property string $to;
 * @property Voucher $voucher;
 * @property User $user;
 * @property StateToken $stateToken;
 * @property Carbon $created_at;
 * @property Carbon $updated_at;
 *
 * Notre sure what these are?  'user_type', 'source',
 */
class VoucherState extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transition',
        'from',
        'user_id',
        'user_type',
        'voucher_id',
        'to',
        'source',
        'state_token_id',
    ];

    /**
     * Inserts a bunch of raw voucher states into the system
     * For speed, we don't check it, we just try it!
     *
     * @param $vouchers
     * @param $time
     * @param $user_id
     * @param $user_type
     * @param $transitionDef
     */
    public static function batchInsert($vouchers, $time, $user_id, $user_type, $transitionDef)
    {
        $states = [];
        foreach ($vouchers as $voucher) {
            // TODO: should we need to, turn this into a VoucherState
            $states[] = [
                'transition' => $transitionDef->name,
                'from' => $voucher->currentState,
                'voucher_id' => $voucher->id,
                'to' => $transitionDef->to,
                'created_at' => $time,
                'updated_at' => $time,
                'source' => "",
                'user_id' => $user_id, // the user ID
                'user_type' => $user_type, // the type of user
            ];
        }
        // Insert this batch of vouchers.
        self::insert($states);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stateToken()
    {
        return $this->belongsTo(StateToken::class);
    }

    /**
     * Gets this voucher state including user details as an array.
     *
     * Named asArray, so it does not override the Model.toArray()
     *
     * @return array
     */
    public function asArray(): array
    {
        $base = $this->toArray();

        $user = null;
        if ($base["user_id"]) {
            $user = [
                "id" => $this->user->id,
                "name" => $this->user->name,
            ];
        }
        $base["user"] = $user;
        unset($base["user_id"]);

        return $base;
    }
}

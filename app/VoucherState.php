<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @mixin Eloquent
 * @property int $id;
 * @property string $transition;
 * @property string $from;
 * @property string $to;
 * @property Voucher $voucher;
 * @property StateToken $stateToken;
 * @property Carbon $created_at;
 * @property Carbon $updated_at;
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
     */
    public static function batchInsert($vouchers, $time, $user_id, $user_type, $transitionDef): void
    {
        $states = [];
        foreach ($vouchers as $voucher) {
            $states[] = [
                'transition' => $transitionDef->name,
                'from' => $voucher->currentState,
                'user_id' => $user_id, // morphTo id
                'user_type' => $user_type, // morphTo type
                'voucher_id' => $voucher->id,
                'to' => $transitionDef->to,
                'source' => "",
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        // Insert this batch of vouchers.
        self::insert($states);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function stateToken(): BelongsTo
    {
        return $this->belongsTo(StateToken::class);
    }
}

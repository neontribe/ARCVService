<?php

namespace App;

use App\Traits\Statable;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Log;
use Throwable;

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
        'bundle_id',
    ];

    /**
     * The attributes that should be case to native types.
     *
     * @var array
     */
    protected $casts = [
        'sponsor_id' => 'int',
        'trader_id' => 'int',
        'bundle_id' => 'int',
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
        'code' => ['required', 'unique:vouchers', 'regex:[A-Z]{2,5}[0-9]{4,8}'],
        // Not sure about this one. We might be able to secify config instead.
        'currentstate' => ['required', 'in_array:voucher_state,to', 'max:24'],
        'sponsor_id' => ['numeric', 'required', 'exists:sponsors,id'],
    ];


    /**
     * Voucher can clean it's codes.
     * @param array $codes
     * @return array
     */
    public static function cleanCodes(array $codes)
    {
        return array_map(
            function ($code) {
                $badChars = [" ",];
                return str_replace($badChars, "", $code);
            },
            $codes
        );
    }

    /**
     * Will generate a code range from start to end in the start range
     *
     * @param string $start voucher code to start with
     * @param string $end voucher code to end with
     * @return array
     */
    public static function generateCodeRange($start, $end = "")
    {
        // There should always be a start. The request will fail before validation before this point if there isn't
        $startMatch = self::splitShortcodeNumeric($start);

        // Gets the whole string match and plumbs it onto the start of the voucher codes.
        $voucherCodes[] = $startMatch[0];

        // Make a range if there's an End value
        if (!empty($end)) {
            $endMatch = self::splitShortcodeNumeric($end);

            // Grab integer versions of each thing.
            $startVal = intval($startMatch['number']);
            $endVal = intval($endMatch['number']);

            // Generate codes!
            for ($val = $startVal + 1; $val <= $endVal; $val++) {
                // Assemble code, add to voucherCodes[]
                // We appear to be producing codes that are "0" str_pad on the left, to variable characters
                // We'll use the $start's numeric length as the value to pad to.
                $voucherCodes[] = $startMatch['shortcode'] . str_pad(
                    $val,
                    strlen($startMatch['number']),
                    "0",
                    STR_PAD_LEFT
                );
            }
        }
        return $voucherCodes;
    }

    /**
     * Splits a voucher code up
     *
     * @param string $code
     * @return array|bool
     */
    public static function splitShortcodeNumeric(string $code)
    {
        // Clean the code
        $clean = self::cleanCodes([$code]);
        $code = array_shift($clean);
        // Init matches
        $matches = [];
        // split into named matche and return
        if (preg_match("/^(?<shortcode>\D*)(?<number>\d+)$/", $code, $matches) == 1) {
             return $matches;
        } else {
            return false;
        }
    }

    /**
     * Gets the ranges of undelivered vouchers
     *
     * @param string $shortcode
     * @return array
     * @throws Throwable
     */
    public static function getUndeliveredVoucherRangesByShortCode(string $shortcode)
    {
        return DB::transaction(function () use ($shortcode) {
            try {
                // Set some important variables for the query. breaks SQLlite.
                DB::statement(DB::raw('SET @initial_id, @initial_serial=0, SET @previous=0;'));

                /* This seems to be the fastest way to find the start and end of each "range" of vouchers;
                 * in this case specified by vouchers that are not in deliveries.
                 * returns an array of stdClass objects with
                 * - serial; the final serial number in the range
                 * - initial_serial; the initial serial in the range
                 * - final_code; the code associated with serial
                 * - initial_code; the code associated with the initial_serial
                 * - id; the voucher id of the serial
                 * - initial_id; the voucher id of the initial_serial
                 */
                // TODO: convert to eloquent
                return DB::select(
                    "
                    SELECT
                        t1.*, 
                        v1.code as intitial_code,
                        v2.code as final_code
                    FROM (
                        
                        SELECT
                            @initial_serial := if(serial - @previous = 1, @initial_serial, serial) as initial_serial,
                            @initial_id := if(serial - @previous = 1, @initial_id, id) as initial_id,
                            @previous := serial as serial,
                            id as final_id
                        FROM (
                        
                            SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as serial
                            FROM vouchers
                            WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                              AND currentstate = 'printed'
                              AND delivery_id is null
                            ORDER BY serial
                        
                        ) as t5
                    
                    ) AS t1
                        INNER JOIN (
                    
                            SELECT initial_serial, max(serial) as final_serial
                            FROM (
                    
                                 SELECT
                                     @initial_serial := if(serial - @previous = 1, @initial_serial, serial) as initial_serial,
                                     @initial_id := if(serial - @previous = 1, @initial_id, id) as initial_id,
                                     @previous := serial as serial,
                                     id
                                 FROM (
                                      SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as serial
                                      FROM vouchers 
                                      WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                                        AND currentstate = 'printed'
                                        AND delivery_id is null
                                      ORDER BY serial
                    
                                 ) as t4
                    
                            ) as t3
                            GROUP BY initial_serial
                    
                        ) as t2
                        ON t1.initial_serial = t2.initial_serial
                          AND t1.serial = t2.final_serial
                    
                    LEFT JOIN vouchers as v1
                        ON initial_id = v1.id
                    
                    LEFT JOIN vouchers as v2
                        ON final_id = v2.id
                    "
                );
            } catch (Throwable $e) {
                Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
                Log::error($e->getTraceAsString());
                return [];
            }
        });
    }


    /**
     * The Sponsor that backs this voucher
     *
     * @return BelongsTo
     */
    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * The Trader that collected this voucher
     *
     * @return BelongsTo
     */
    public function trader()
    {
        return $this->belongsTo(Trader::class);
    }

    /**
     * The bundle that A CC may have collated this voucher into
     *
     * @return BelongsTo
     */
    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * The Delivery the voucher was attached to
     *
     * @return BelongsTo
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the most recent voucher_state change to payment_pending.
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return VoucherState
     */
    public function paymentPendedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'payment_pending')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Get the most recent voucher_state change to recorded.
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return VoucherState
     */
    public function recordedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'recorded')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Get the most recent voucher_state change to reimbursed.
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return VoucherState
     */
    public function reimbursedOn()
    {
        return $this->hasOne(VoucherState::class)
                ->where('to', 'reimbursed')
                ->orderBy('created_at', 'desc');
    }

    /**
     * Limit Vouchers to ones that have been confirmed for payment.
     * This will include both pending and reimbursed vouchers.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeConfirmed($query)
    {
        $states = ['payment_pending', 'reimbursed'];
        return $query->whereIn('currentstate', $states);
    }

    /**
     * @param $code
     * @return Voucher
     */
    public static function findByCode($code)
    {
        return self::where('code', $code)->get()->first();
    }

    /**
     * @param $codes
     * @return Collection
     */
    public static function findByCodes($codes)
    {
        return self::whereIn('code', $codes)->get();
    }

  /**
   * Retrieve the min and max paymentPendedOn date of a collection of vouchers.
   *
   * @param Collection $vouchers
   *
   * @return array
   */
    public static function getMinMaxVoucherDates(Collection $vouchers)
    {
        $sorted_vouchers = $vouchers->sortBy(function ($voucher) {
            return $voucher->paymentPendedOn->created_at->timestamp;
        })->values()->all();

        $min_date = $sorted_vouchers[0]->paymentPendedOn->created_at->format('d-m-Y');
        $max_date = $sorted_vouchers[count($sorted_vouchers) - 1]->paymentPendedOn->created_at->format('d-m-Y');

        // If max date is the same as min date return null.
        $max_date = ($min_date === $max_date) ? null : $max_date;

        return [$min_date, $max_date];
    }
}

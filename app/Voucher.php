<?php

namespace App;

use App\Traits\Statable;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * Gets the range that contains our range from an array of ranges
     *
     * @param $start
     * @param $end
     * @param $ranges array of range objects.
     *
     * @return object|null
     */
    private static function getContainingRange($start, $end, array $ranges)
    {
        /** @var object $range */
        foreach ($ranges as $range) {
            // Are Start and End both in the range?
            if ($start >= $range->start &&
                $end <= $range->end &&
                $start <= $end ) {
                return $range;
            };
        }
        return null;
    }

    /**
     * Creates a rangeDef structure
     * TODO: convert to class?
     *
     * @param string $startCode
     * @param string $endCode
     * @return object
     */
    public static function createRangeDefFromVoucherCodes($startCode, $endCode)
    {
        // Add the sponsor's id, use the start code.
        $rangeDef['sponsor_id'] = self::where('code', $startCode)->firstOrFail()->sponsor_id;

        // Slightly complicated way of making an object that represents the range.
        // Destructure the output of into an assoc array
        [ 'shortcode' => $rangeDef['shortcode'], 'number' => $rangeDef['start'] ] = self::splitShortcodeNumeric($startCode);
        [ 'number' => $rangeDef['end'] ] = self::splitShortcodeNumeric($endCode);

        // Modify the start/end numbers to integers
        $rangeDef["start"] = intval($rangeDef["start"]);
        $rangeDef["end"] = intval($rangeDef["end"]);

        return (object) $rangeDef;
    }

    /**
     * Determines if the given voucher range contains entries already delivered
     *
     * @param $rangeDef object { 'start', 'end', 'shortcode', 'sponsor_id' }
     * @return bool
     */
    public static function rangeIsDeliverable($rangeDef)
    {
        $ranges = self::getDeliverableVoucherRangesByShortCode($rangeDef->shortcode);
        $range = self::getContainingRange($rangeDef->start, $rangeDef->end, $ranges);
        return (!is_null($range) && is_object($range));
    }

    /**
     * Determines if the given voucher range cannot be voided
     *
     * @param $rangeDef object { 'start', 'end', 'shortcode', 'sponsor_id' }
     * @return bool
     */
    public static function rangeIsVoidable($rangeDef)
    {
        $ranges = self::getVoidableVoucherRangesByShortCode($rangeDef->shortcode);
        $range = self::getContainingRange($rangeDef->start, $rangeDef->end, $ranges);
        return (!is_null($range) && is_object($range));
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
     * Gets ranges of the vouchers that *can* be voided, by a Sponsor's shortcode.
     *
     * @param string $shortcode
     * @return array
     */
    public static function getVoidableVoucherRangesByShortCode(string $shortcode)
    {
        try {
            return DB::transaction(function () use ($shortcode) {
                // Set some important variables for the query. breaks SQLlite.
                DB::statement(DB::raw('SET @initial_id=0, @start=0, @previous=0;'));

                /* This seems to be the fastest way to find the start and end of each "range" of vouchers;
                 * in this case specified by vouchers that are not in deliveries.
                 * returns an array of stdClass objects with
                 * - serial; the final serial number in the range
                 * - start; the initial serial in the range
                 * - final_code; the code associated with serial
                 * - initial_code; the code associated with the start
                 * - id; the voucher id of the serial
                 * - initial_id; the voucher id of the start
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
                            @start := if(end - @previous = 1, @start, end) as start,
                            @initial_id := if(end - @previous = 1, @initial_id, id) as initial_id,
                            @previous := end as end,
                            id as final_id
                        FROM (
                        
                            SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as end
                            FROM vouchers
                            WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                              AND currentstate = 'dispatched'
                            ORDER BY end
                        
                        ) as t5
                    
                    ) AS t1
                        INNER JOIN (
                    
                            SELECT start, max(end) as final
                            FROM (
                    
                                 SELECT
                                     @start := if(end - @previous = 1, @start, end) as start,
                                     @initial_id := if(end - @previous = 1, @initial_id, id) as initial_id,
                                     @previous := end as end,
                                     id
                                 FROM (
                                     
                                    SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as end
                                    FROM vouchers
                                    WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                                      AND currentstate = 'dispatched'
                                    ORDER BY end
                    
                                 ) as t4
                    
                            ) as t3
                            GROUP BY start
                    
                        ) as t2
                        ON t1.start = t2.start
                          AND t1.end = t2.final
                    
                    LEFT JOIN vouchers as v1
                        ON initial_id = v1.id
                    
                    LEFT JOIN vouchers as v2
                        ON final_id = v2.id
                    "
                );
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            return [];
        }
    }

    /**
     * Gets the ranges of undelivered vouchers
     *
     * @param string $shortcode
     * @return array
     */
    public static function getDeliverableVoucherRangesByShortCode(string $shortcode)
    {
        try {
            return DB::transaction(function () use ($shortcode) {

                // Set some important variables for the query. breaks SQLlite.
                DB::statement(DB::raw('SET @initial_id=0, @start=0, @previous=0;'));

                /* This seems to be the fastest way to find the start and end of each "range" of vouchers;
                 * in this case specified by vouchers that are not in deliveries.
                 * returns an array of stdClass objects with
                 * - end; the final end number in the range
                 * - start; the initial end in the range
                 * - final_code; the code associated with end
                 * - initial_code; the code associated with the start
                 * - id; the voucher id of the end
                 * - initial_id; the voucher id of the start
                 */
                // TODO: convert to eloquent
                return DB::select(
                    "
                    SELECT
                        # Resolves the sub-queries operation to a table of ranges
                        t1.*, 
                        v1.code as intitial_code,
                        v2.code as final_code
                    FROM (
                        SELECT
                            # Variables! allows us to compare the _actual_ start ranges of vouchers.
                            @start := if(end - @previous = 1, @start, end) as start,
                            @initial_id := if(end - @previous = 1, @initial_id, id) as initial_id,
                            @previous := end as end,
                            id as final_id
                        FROM (
                            # sub-query that gets the vouchers in a state we want under a specific shortcode. 
                            SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as end
                            FROM vouchers
                            WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                              AND currentstate = 'printed'
                              AND delivery_id is null
                            ORDER BY end
                        
                        ) as t5
                    
                    ) AS t1
                        INNER JOIN (
                            # Bit of a self join going on in this one to find the end values.
                            SELECT start, max(end) as final
                            FROM (
                                 # Look familiar? should do, it's the same as the above, for a comaprative join.
                                 SELECT
                                     @start := if(end - @previous = 1, @start, end) as start,
                                     @initial_id := if(end - @previous = 1, @initial_id, id) as initial_id,
                                     @previous := end as end,
                                     id
                                 FROM (
                                      SELECT id, cast(replace(code, '{$shortcode}', '') as signed) as end
                                      FROM vouchers 
                                      WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                                        AND currentstate = 'printed'
                                        AND delivery_id is null
                                      ORDER BY end
                    
                                 ) as t4
                    
                            ) as t3
                            GROUP BY start
                    
                        ) as t2
                        ON t1.start = t2.start
                          AND t1.end = t2.final
                    
                    LEFT JOIN vouchers as v1
                        ON initial_id = v1.id
                    
                    LEFT JOIN vouchers as v2
                        ON final_id = v2.id
                    "
                );
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            return [];
        }
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
     * @return HasOne
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
     * @return HasOne
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
     * @return HasOne
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
     * @param string $code
     * @return Voucher
     */
    public static function findByCode($code)
    {
        return self::where('code', $code)->first();
    }

    /**
     * @param array $codes
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

    /**
     * Gets a set of vouchers that share a shortcode and currentstate in a sponsor
     *
     * @param Builder $query
     * @param object $rangeDef
     * @param string $state State if interest
     * @return Builder
     */
    public function scopeWithRangedVouchersInState(Builder $query, $rangeDef, $state)
    {
        return $query
            ->where('currentstate', $state)
            ->where('code', 'REGEXP', "^{$rangeDef->shortcode}[0-9]+\$") // Just vouchers that start with our shortcode
            ->where('sponsor_id', $rangeDef->sponsor_id) // that are in the sponsor (performance, using the index)
            ->whereBetween(
                DB::raw("cast(replace(code, '{$rangeDef->shortcode}', '') as signed)"),
                [$rangeDef->start, $rangeDef->end]
            );
    }
}

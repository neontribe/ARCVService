<?php

namespace App;

use App\Traits\Statable;
use Auth;
use DateTimeInterface;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * @mixin Eloquent
 * @property integer $id
 * @property string $code
 * @property Sponsor $sponsor
 * @property Trader $trader
 * @property Bundle $bundle
 * @property Delivery $delivery
 * @property string $rvid
 * @property VoucherState $paymentPendedOn
 * @property VoucherState $recordedOn
 * @property VoucherState $reimbursedOn
 */

class Voucher extends Model
{
    use Statable;

    // import the state transition stuff.

    use SoftDeletes;

    public const HISTORY_MODEL = 'App\VoucherState'; // the related model to store the history
    public const SM_CONFIG = 'Voucher'; // the SM graph to use

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
        'deleted_at' => 'datetime',
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
    public static array $rules = [
        // Might need to add a 'sometimes' if any required fields can be absent from requests.
        'trader_id' => ['numeric', 'exists:traders,id'],
        // My regex might be pants... but until we get the edit form spun up who cares?
        'code' => ['required', 'unique:vouchers', 'regex:[A-Z]{2,5}[0-9]{4,8}'],
        // Not sure about this one. We might be able to secify config instead.
        'currentstate' => ['required', 'in_array:voucher_state,to', 'max:24'],
        'sponsor_id' => ['numeric', 'required', 'exists:sponsors,id'],
    ];


    /**
     * Voucher can clean its codes.
     * @param array $codes
     * @return array
     */
    public static function cleanCodes(array $codes): array
    {
        return array_map(
            static function ($code) {
                return str_replace(" ", "", $code);
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
            $startVal = (int)$startMatch['number'];
            $endVal = (int)$endMatch['number'];

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
    private static function getContainingRange($start, $end, array $ranges): ?object
    {
        /** @var object $range */
        foreach ($ranges as $range) {
            // Are Start and End both in the range?
            if (
                $start <= $end &&           // query is properly formed
                $start >= $range->start &&  // our start is gte range start
                $end <= $range->end         // our end is lte range end
            ) {
                // early return on success
                return $range;
            }
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
        ['shortcode' => $rangeDef['shortcode'],
            'number' => $rangeDef['start']] = self::splitShortcodeNumeric($startCode);
        ['number' => $rangeDef['end']] = self::splitShortcodeNumeric($endCode);

        // Modify the start/end numbers to integers
        $rangeDef["start"] = (int)$rangeDef["start"];
        $rangeDef["end"] = (int)$rangeDef["end"];

        return (object)$rangeDef;
    }

    /**
     * Determines if the given voucher range contains entries already delivered
     *
     * @param $rangeDef object { 'start', 'end', 'shortcode', 'sponsor_id' }
     * @return bool
     */
    public static function rangeIsDeliverable(object $rangeDef): bool
    {
        $freeRangesArray = self::getDeliverableVoucherRangesByShortCode($rangeDef->shortcode);
        $containingRange = self::getContainingRange($rangeDef->start, $rangeDef->end, $freeRangesArray);
        return is_object($containingRange);
    }

    /**
     * Splits a voucher code up
     *
     * @param string $code
     * @return array|bool
     */
    public static function splitShortcodeNumeric(string $code): bool|array
    {
        // Clean the code
        $clean = self::cleanCodes([$code]);
        $code = array_shift($clean);
        // Init matches
        $matches = [];
        // split into named matches and return
        return preg_match("/^(?<shortcode>\D*)(?<number>\d+)$/", $code, $matches) === 1
            ? $matches
            : false;
    }

    /**
     * Gets the ranges of undelivered vouchers
     *
     * @param string $shortcode
     * @return array
     */
    public static function getDeliverableVoucherRangesByShortCode(string $shortcode): array
    {
        try {
            return DB::transaction(static function () use ($shortcode) {

                // Set some important variables for the query. breaks SQLlite.
                DB::statement('SET @t5initialId=0, @t5start=0, @t5previous=0, @t4initialId=0, @t4start=0, @t4previous=0;');

                /* This seems to be the fastest way to find the start and end of each "range" of vouchers;
                 * in this case specified by vouchers that are not in deliveries.
                 * returns an array of stdClass objects with
                 * - end; the final end number in the range
                 * - start; the initial end in the range
                 * - final_code; the code associated with end
                 * - initial_code; the code associated with the start
                 * - id; the voucher id of the end
                 * - initial_id; the voucher id of the start
                 * - size; the number of vouchers in the range
                 */
                // TODO: convert to eloquent
                return DB::select(
                    "
                    SELECT
                        # Resolves the sub-queries operation to a table of ranges
                        t1.*,
                        v1.code as initial_code,
                        v2.code as final_code,
                        (t1.end - t1.start) + 1 as size
                    FROM (
                        SELECT
                            # Variables! allows us to compare the _actual_ start ranges of vouchers.
                            @t5start := if(end - @t5previous = 1, @t5start, end) as start,
                            @t5initialId := if(end - @t5previous = 1, @t5initialId, id) as initial_id,
                            @t5previous := end as end,
                            id as final_id
                        FROM (
                            # T5 sub-query that gets the vouchers in a state we want under a specific shortcode.
                            SELECT cast(replace(code, '{$shortcode}', '') as signed) as end, id
                            FROM vouchers
                            WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                              AND currentstate = 'printed'
                              AND delivery_id is null
                            ORDER BY end, id
                        ) as t5
                        ORDER by start, end
                    ) AS t1
                        INNER JOIN (
                            # Bit of a self join going on in this one to find the end values.
                            SELECT start, max(end) as final
                            FROM (
                                 # Look familiar? should do, it's the same as the above, for a comparative join.
                                 SELECT
                                     @t4start := if(end - @t4previous = 1, @t4start, end) as start,
                                     @t4initialId := if(end - @t4previous = 1, @t4initialId, id) as initial_id,
                                     @t4previous := end as end,
                                     id
                                 FROM (
                                      SELECT cast(replace(code, '{$shortcode}', '') as signed) as end, id
                                      FROM vouchers
                                      WHERE code REGEXP '^{$shortcode}[0-9]+\$'
                                        AND currentstate = 'printed'
                                        AND delivery_id is null
                                      ORDER BY end, id
                                 ) as t4

                            ) as t3
                            GROUP BY start
                            ORDER BY start

                        ) as t2
                        ON t1.start = t2.start
                          AND t1.end = t2.final

                    LEFT JOIN vouchers as v1
                        ON initial_id = v1.id

                    LEFT JOIN vouchers as v2
                        ON final_id = v2.id
                        
                    ORDER BY t1.start
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
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * The Trader that collected this voucher
     *
     * @return BelongsTo
     */
    public function trader(): BelongsTo
    {
        return $this->belongsTo(Trader::class);
    }

    /**
     * The bundle that A CC may have collated this voucher into
     *
     * @return BelongsTo
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * The Delivery the voucher was attached to
     *
     * @return BelongsTo
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the most recent voucher_state change to payment_pending.
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return HasOne
     */
    public function paymentPendedOn(): HasOne
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
    public function recordedOn(): HasOne
    {
        return $this->hasOne(VoucherState::class)
            ->where('to', 'recorded')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the most recent voucher_state change to 'reimbursed'.
     * There should only ever be one per voucher - but most recent safer.
     *
     * @return HasOne
     */
    public function reimbursedOn(): HasOne
    {
        return $this->hasOne(VoucherState::class)
            ->where('to', 'reimbursed')
            ->orderBy('created_at', 'desc');
    }

    public function rvid(): ?string
    {
        $centreSequence = $this->bundle?->registration->family->centre_sequence;
        $centrePrefix = $this->bundle?->registration->family->initialCentre->prefix;
        if ($centreSequence) {
            return $centrePrefix . str_pad($centreSequence, 4, "0", STR_PAD_LEFT);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function voucherHasBeenResurrected(): bool
    {
        $vs = $this->history()->get()->last();
        if ($vs) {
            return $vs->to != "reimbursed";
        }

        // ???? can a voucher have no state?
        return false;
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
     * Gets a set of vouchers in a range using shortcode, sponsor and voucher number.
     *
     * @param Builder $query
     * @param object $rangeDef
     * @return Builder
     */
    public function scopeInDefinedRange($query, $rangeDef)
    {
        return $query
            ->where('code', 'REGEXP', "^{$rangeDef->shortcode}[0-9]+\$") // Just vouchers that start with our shortcode
            ->where('sponsor_id', $rangeDef->sponsor_id) // that are in the sponsor (performance, using the index)
            ->whereBetween(
                DB::raw("cast(replace(code, '{$rangeDef->shortcode}', '') as signed)"),
                [$rangeDef->start, $rangeDef->end]
            );
    }

    /**
     * Gets a set of vouchers that are in one of given states.
     *
     * @param Builder $query
     * @param array $states
     * @return Builder
     */
    public function scopeInOneOfStates(Builder $query, $states)
    {
        return $query
            ->whereIn('currentstate', $states);
    }

    /**
     * Gets a set of vouchers that are voidable.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInVoidableState(Builder $query)
    {
        $voidable_states = ['dispatched'];
        return $query
            ->inOneOfStates($voidable_states);
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getVoucherStateHistory(): array
    {
        $vss = VoucherState::where("voucher_id", $this->id)->orderBy('updated_at')->get();
        $states = [];
        if ($vss) {
            foreach ($vss as $vs) {
                $states[] = [
                    "id" => $this->id,
                    "transition" => $vs->transition,
                    "from" => $vs->from,
                    "user_id" => $vs->user_id,
                    "user_type" => $vs->user_type,
                    "voucher_id" => $vs->voucher_id,
                    "to" => $vs->to,
                    "state_token_id" => $vs->state_token_id,
                    "source" => $vs->source,
                    "created_at" => $vs->created_at,
                    "updated_at" => $vs->updated_at,
                ];
            }
        }
        return $states;
    }

    public function deepExport(bool $includeVoucherStates = false): array
    {
        $v = [
            "code" => $this->code,
            "sponsor_name" => $this->sponsor?->name,
            "dispatched_at" => $this->delivery?->dispatched_at->format("Y/m/d"),
            "delivery_centre_name" => $this->delivery?->centre->name,
            "centre_sponsor_name" => $this->delivery?->centre->sponsor->name,
            "disbursed" => $this->bundle?->disbursed_at ? "True" : "False",
            "disbursed_at" => $this->bundle?->disbursed_at?->format("Y/m/d"),
            "rvid" => (string)$this->rvid(),
            "primary_carer" => $this->bundle?->registration->family->carers[0]->name,
            "registration_centre_name," => $this->bundle?->registration->centre->name,
            "recordedOn" => $this->recordedOn->created_at->format("Y/m/d"),
            "trader_name" => $this->trader?->name,
            "trader_market_name" => $this->trader?->market->name,
            "trader_market_sponsor_name" => $this->trader?->market->sponsor->name,
            "paymentPendedOn" => $this->paymentPendedOn->created_at->format("Y/m/d"),
            "reimbursedOn" => $this->reimbursedOn->created_at->format("Y/m/d"),
        ];
        if ($includeVoucherStates) {
            $v["vouchere_states"] = $this->getVoucherStateHistory();
        }
        return $v;
    }
}

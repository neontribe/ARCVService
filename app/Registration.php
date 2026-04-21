<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Evaluable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Centre;
use App\Family;

/**
 * @mixin Eloquent
 * @property string $eligibility_hsbs
 * @property string $eligibility_nrpf
 * @property string $consented_on
 * @property string $eligible_from
 * @property string $created_at
 * @property string $updated_at
 * @property bool $isActive
 * @property Family $family
 * @property Centre $centre
 * @property Bundle $currentBundle
 * @property Bundle[] $bundles
 */
class Registration extends Model implements IEvaluee
{
    use Evaluable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'eligibility_hsbs',
        'eligibility_nrpf',
        'consented_on',
        'eligible_from'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * These are turned into Date objects on get
     *
     * @var array
     */

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'consented_on' => 'datetime',
        'eligible_from' => 'datetime',
    ];

    /**
     * Magically gets a public evaluator.
     * @return AbstractEvaluator
     */
    public function getEvaluator(): AbstractEvaluator
    {
        // if the private var is null, make a new one, stash it and return it.
        $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::makeFromRegistration($this);
        return $this->_evaluator;
    }

    /**
     * Works out if a Registration can be counted as "Active"
     */
    public function isActive(): bool
    {
        // Get the last disbursement, if any.
        $lastCollection = $this->bundles()
            ->disbursed()
            ->orderBy('disbursed_at', 'desc')
            ->first()
        ;
        // Use created_at, aka "Join Date" if no collections (edge case)
        /** @var Carbon $activeDate */
        $activeDate = ($lastCollection->disbursed_at) ?? $this->created_at;

        /*  if today() is less than or equal to
                Friday of the 4th week after pickup
                    then true, else false
        */

        // Calculate forward date.
        $friday4thWeek = $activeDate
            // find start of that week (day 1, monday) ...
            ->startOfWeek()
            // add four full weeks ...
            ->addWeeks(4)
            // add 4 extra days (day 1 -> 5, friday)
            ->addDays(4)
        ;
        return Carbon::today()->lessThanOrEqualTo($friday4thWeek);
    }

    /**
     * Get the Registration's Family
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the Registration's Centre
     */
    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Get the first un-disbursed bundle on a Registration for any Centre.
     * There should only be one... else make one if there are none.
     *
     * @return Model
     */
    public function currentBundle(): Model
    {
        $bundle = $this->bundles()
            ->where('disbursed_at', null)
            ->where('registration_id', $this->id)
            ->orderBy('id', 'asc')
            ->first()
        ;

        if (!$bundle) {
            $bundle = Bundle::create([
                "registration_id" => $this->id,
                "entitlement" => $this->getValuation()->getEntitlement()
                ]);
        }

        return $bundle;
    }

    /**
     * Get the Registrations's Bundles
     *
     * @return HasMany
     */
    public function bundles(): HasMany
    {
        return $this->hasMany('App\Bundle');
    }

    /**
     * Fetches the Registrations full Family and dependent models.
     */
    public function scopeWithFullFamily(Builder $query): Builder
    {
        return $query->with([
            // This may not be efficient, but it is convenient for ordering when required.
            'family' => function ($q) {
                $q->withPrimaryCarer();
            },
            'family.children',
            'family.carers',
        ]);
    }

    /**
     * Fetches only Registrations with an Active Family
     */
    public function scopeWhereActiveFamily(Builder $query): Builder
    {
        return $query->whereHas('family', function ($q) {
            $q->whereNull('leaving_on');
            $q->orWhereColumn('rejoin_on', '>', 'leaving_on');
        });
    }

    public function lastBundle(): HasOne
    {
        return $this->hasOne(Bundle::class)
            ->whereNotNull('disbursed_at')
            ->latest('disbursed_at');
    }

    /**
     * Join the primary carer (MIN id per family) directly into the query,
     * so we can filter and sort by carer name in SQL rather than PHP.
    */
    public function scopeWithPrimaryCarer(Builder $query): Builder
    {
        return $query
            ->select('registrations.*')
            ->joinSub(
                Carer::query()
                    ->selectRaw('MIN(id) AS id, family_id')
                    ->groupBy('family_id'),
                'pri_carers',
                'pri_carers.family_id', '=', 'registrations.family_id'
            )
            ->join('carers', 'carers.id', '=', 'pri_carers.id');
    }

    public function scopeOrderByCarerName(Builder $query, bool $descending = false): Builder
    {
        return $query->orderBy('carers.name', $descending ? 'desc' : 'asc');
    }

    public function scopeFilterByCarerName(Builder $query, string $term): Builder
    {
        return $query
            ->where('carers.name', 'LIKE', "%{$term}%")
            ->orderByRaw(
                "CASE
                WHEN LOWER(carers.name) = LOWER(?)         THEN 0
                WHEN LOWER(carers.name) LIKE LOWER(?)      THEN 1
                WHEN LOWER(carers.name) LIKE LOWER(?)
                  OR LOWER(carers.name) LIKE LOWER(?)      THEN 2
                ELSE 3
            END",
                [$term, "{$term} %", "% {$term} %", "% {$term}"]
            );
    }
}

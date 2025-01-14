<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Evaluable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     *
     * @return bool
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
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo('App\Family');
    }

    /**
     * Get the Registration's Centre
     *
     * @return BelongsTo
     */
    public function centre(): BelongsTo
    {
        return $this->belongsTo('App\Centre');
    }

    /**
     * Get the first un-disbursed bundle on a Registration for any Centre.
     * There should only be one... else make one if there are none.
     *
     * @return Model
     */
    public function currentBundle()
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
     * @param $query
     * @return mixed
     */
    public function scopeWithFullFamily($query)
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
     * @param $query
     * @return mixed
     */
    public function scopeWhereActiveFamily($query)
    {
        return $query->whereHas('family', function ($q) {
            $q->whereNull('leaving_on');
            $q->orWhereColumn('rejoin_on', '>', 'leaving_on');
        });
    }
}

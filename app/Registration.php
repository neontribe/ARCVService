<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluation;
use App\Services\VoucherEvaluator\IEvaluee;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model implements IEvaluee
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'eligibility',
        'consented_on'
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

    protected $dates = [
        'created_at',
        'updated_at',
        'consented_on',
    ];

    /** @var  AbstractEvaluator null  */
    public $evaluator = null;

    /**
     * Run a valuation on this registration.
     */
    public function getValuationAttribute()
    {
        // Get the evaluator, or make a new one
        $this->evaluator = $this->evaluator ?? EvaluatorFactory::makeFromRegistration($this);
        // Start the process of making a valuation
        $this->accept($this->evaluator);
        // fetch the report
        return $this->evaluator->valuation;
    }

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        $evaluator->evaluateRegistration($this);
    }

    /**
     * Works out if a Registration can be counted as "Active"
     *
     * @return bool
     */
    public function isActive()
    {
        // Get the last disbursement, if any.
        $lastCollection = $this->bundles()
            ->disbursed()
            ->orderBy('disbursed_at', 'desc')
            ->first()
        ;

        if ($lastCollection) {
            /*
            for ARC's requirements,
                if today() is less than or equal to
                    Friday of the 4th week after pickup
                        then true;
                else
                    false
            */

            // get actual date
            /** @var Carbon $pickupDate */
            $pickupDate = $lastCollection->disbursed_at;

            // Calculate forward date.
            $friday4thWeek = $pickupDate->copy()
                // set to Monday of week picked up
                ->startOfWeek()
                // add three full weeks
                ->addWeeks(3)
                // Friday of 4th week.
                ->addDays(4)
            ;

            if (Carbon::today()->lessThanOrEqualTo($friday4thWeek)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the Registration's Family
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function family()
    {
        return $this->belongsTo('App\Family');
    }

    /**
     * Get the Registration's Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function centre()
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
                "entitlement" => $this->valuation->getEntitlement()
                ]);
        };

        return $bundle;
    }

    /**
     * Get the Registrations's Bundles
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bundles()
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
        });
    }
}

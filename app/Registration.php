<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Evaluable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model implements IEvaluee
{
    use Evaluable;

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

    /**
     * Magically gets a public evaluator.
     * @return AbstractEvaluator
     */
    public function getEvaluator()
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
    public function isActive()
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
    public function family()
    {
        return $this->belongsTo('App\Family');
    }

    /**
     * Get the Registration's Centre
     *
     * @return BelongsTo
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
                "entitlement" => $this->getValuation()->getEntitlement()
                ]);
        };

        return $bundle;
    }

    /**
     * Get the Registrations's Bundles
     *
     * @return HasMany
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

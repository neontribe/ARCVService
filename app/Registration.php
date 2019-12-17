<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Services\VoucherEvaluator\Valuation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Private evaluator stash
     * @var AbstractEvaluator null
     */
    private $_evaluator = null;

    /**
     * Valuation stash
     * @var Valuation $_valuation
     */
    public $_valuation = null;

    /**
     * Magically gets a public evaluator.
     * @return AbstractEvaluator
     */
    public function getEvaluatorAttribute()
    {
        // if the private var is null, make a new one, stash it and return it.
        $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::makeFromRegistration($this);
        return $this->_evaluator;
    }

    /**
     * Get the valuation on this registration.
     *
     * @return Valuation
     */
    public function getValuationAttribute()
    {
        // Get the stashed one, or get a new one.
        return ($this->_valuation) ?? $this->accept($this->evaluator);
    }

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     * @return Valuation
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        $this->_valuation = $evaluator->evaluate($this);
        return $this->_valuation;
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
                "entitlement" => $this->valuation->getEntitlement()
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

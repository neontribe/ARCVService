<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Services\VoucherEvaluator\Valuation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Child extends Model implements IEvaluee
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dob','born'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * These are turned into Date objects on get
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'dob'
    ];

    /**
     * Get's the family's preferred evaluator
     *
     * @return AbstractEvaluator
     */
    public function getEvaluatorAttribute()
    {
        return $this->family->evaluator();
    }

    /**
     * Get the valuation on this child.
     *
     * @return Valuation
     */
    public function getValuationAttribute()
    {
        return $this->accept($this->evaluator());
    }

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     * @return Valuation
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        return $evaluator->evaluateChild($this);
    }

    /**
     * Calculates and returns the age in Years and Months (or P for pregnancy)
     *
     * @param string $format
     * @return string
     */
    public function getAgeString($format = '%y yr, %m mo')
    {
        $currentDate = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $currentDatePlusOne = Carbon::instance($currentDate)->addDays(1);

        if ($this->dob->isFuture()) {
            return "P";
        } else if ($currentDate == $startOfMonth) {
            // Return 2nd of month as on the first of every month
            // Carbon treats it as the previous month and returns
            // A month less than it should be.
            return $this->dob->diff($currentDatePlusOne)->format($format);
        } else {
            return $this->dob->diff($currentDate)->format($format);
        }
    }

    /**
     * Returns the DoB as a string
     *
     * @param string $format
     * @return string
     */
    public function getDobAsString($format = 'M Y')
    {
        return $this->dob->format($format);
    }

    /**
     * Generic future date calculator, uses to school start and extended start
     *
     * @param int $years
     * @param int|null $month
     * @return Carbon
     */
    public function calcFutureMonthYear(int $years, int $month = null)
    {
        $month = ($month) ?? config('arc.school_month');
        $years = ($this->dob->month < $month)
            ? $years -1
            : $years
        ;
        $future_year = $this->dob->addYears($years)->year;
        return Carbon::createFromDate($future_year, $month, 1);
    }

    /**
     * Calculates the School start date for a Child
     * If a Child is born before september, 4 years ahead
     * Else 5 years ahead
     *
     * @return Carbon
     */
    public function calcSchoolStart()
    {
        return $this->calcFutureMonthYear(5);
    }

    /**
     * Get this Child's Family.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function family()
    {
        return $this->belongsTo('App\Family');
    }
}

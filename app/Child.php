<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\IEvaluee;
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
        'dob'
    ];

    protected $appends = [
        'entitlement',
    ];

    private $evaluation = [];

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
     * Calculates the School start date for a Child
     * If a Child is born before september, 4 years ahead
     * Else 5 years ahead
     *
     * @return Carbon
     */
    public function calcSchoolStart()
    {
        $school_month = config('arc.school_month');
        if ($this->dob->month < $school_month) {
            $years = 4;
        } else {
            $years = 5;
        }
        $school_year = $this->dob->addYears($years)->year;
        //day needs to be set to one as carbon gets confused on 31st
        return Carbon::createFromDate($school_year, $school_month, 1);
    }

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     * @return array
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        $this->setEvaluation($evaluator->evaluateChild($this));
        return $this->getEvaluation();
    }

    private function setEvaluationAttribute($array)
    {
        $this->evaluation = $array;
    }
    /**
     * Get an array that holds
     * Notices - array of Notice constants
     * Credits - array of Credit constants
     * Eligibility - status of child on scheme
     * Vouchers - total vouchers this child is permitted
     *
     * These can be used in voucher multipliers
     *
     * @param Carbon|bool $offsetDate The date to compare the DOB to.
     * @return array
     */
    public function getEvaluation()
    {
        return $this->evaluation;
    }

    /**
     * Get eligibility value string for Blade.
     *
     * @return mixed|string
     */
    public function getStatusString()
    {
        return $this->getEvaluation()['eligibility'];
    }

    /**
     * Calculates the entitlement for a child
     *
     * @return int
     */
    public function getEntitlementAttribute()
    {
        return $this->getStatus()['entitlement'];
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

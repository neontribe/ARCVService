<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Aliasable;
use App\Traits\Evaluable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $dob
 * @property bool $born
 * @property bool $verified
 * @property bool $defer
 * @property bool $is_pri_carer
 * @property Family $family
 */
class Child extends Model implements IEvaluee
{
    use Aliasable;
    use Evaluable;

    public const PROGRAMME_ALIASES = [
        "Child",
        "Participant",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dob',
        'born',
        'verified',
        'deferred',
        'is_pri_carer',
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
     * The attributes that should be appended.
     *
     * @var array
     */
    protected $appends = ['can_defer'];

    /**
     * Get's the family's preferred evaluator
     *
     * @return AbstractEvaluator
     */
    public function getEvaluator(): AbstractEvaluator
    {
        if ($this->has('family')) {
            return $this->family->getEvaluator();
        }
        $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::make();
        return $this->_evaluator;
    }

    /*
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'verified' => 'boolean',
        'deferred' => 'boolean',
    ];

    /**
     * Calculates and returns the age in Years and Months (or P for pregnancy)
     *
     * @param string $format
     * @return string
     */
    public function getAgeString(string $format = '%y yr, %m mo') : string
    {
        $currentDate = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $currentDatePlusOne = Carbon::instance($currentDate)->addDays(1);

        if ($this->dob->isFuture()) {
            return "P";
        }

        if ($currentDate == $startOfMonth) {
            // Return 2nd of month as on the first of every month
            // Carbon treats it as the previous month and returns
            // A month less than it should be.
            return $this->dob->diff($currentDatePlusOne)->format($format);
        }

        return $this->dob->diff($currentDate)->format($format);
    }

    /**
     * Returns the DoB as a string
     *
     * @param string $format
     * @return string
     */
    public function getDobAsString(string $format = 'M Y'): string
    {
        return $this->dob->format($format);
    }

    /**
     * Generic future date calculator, uses to school start and extended start
     *
     * @param int $years Years to look ahead
     * @param int|null $month A specific month for "end of year"
     * @return Carbon
     */
    public function calcFutureMonthYear(int $years, int $month = null): Carbon
    {
        // Take the month we're given, or default to the config
        $month = ($month) ?? config('arc.school_month');
        // If we're born BEFORE the month
        $years = ($this->dob->month < $month)
            // ... then we'll start one year earlier
            ? $years -1
            // ... else we're a late starter and it'll be the number given.
            : $years
        ;
        // Calculate our birth year in that many years time
        $future_year = $this->dob->addYears($years)->year;
        // Return the desired month in that many years time
        return Carbon::createFromDate($future_year, $month, 1);
    }

    /**
     * Calculates the School start date for a Child
     * If a Child is born before september, 4 years ahead
     * Else 5 years ahead
     *
     * @return Carbon
     */
    public function calcSchoolStart(): Carbon
    {
        return $this->calcFutureMonthYear(5);
    }

    /**
     * Get this Child's Family.
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get info about whether child can defer
     *
     * @return boolean
     */
    public function getCanDeferAttribute(): bool
    {
        $notices = $this->getEvaluator()->evaluations["App\Child"]["notices"] ?? [];
        if (!array_key_exists('ScottishChildCanDefer', $notices)) {
            return false;
        }
        $evaluation = $this->getEvaluator()->evaluate($this);
        $notices = $evaluation['notices'];

        if (count($notices) > 0) {
            foreach ($notices as $key => $notice) {
                if (array_key_exists('reason', $notice) && $notice['reason'] === 'Child|able to defer (SCOTLAND)') {
                    return true;
                }
            }
        }
        return false;
    }
}

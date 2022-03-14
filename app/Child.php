<?php

namespace App;

use App\Evaluation;
use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Evaluable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Child extends Model implements IEvaluee
{
    use Evaluable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dob','born', 'verified', 'deferred'
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
    public function getEvaluator()
    {
      // $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::make();
      // return $this->_evaluator;
        return $this->family->getEvaluator();
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
     * @param int $years Years to look ahead
     * @param int|null $month A specific month for "end of year"
     * @return Carbon
     */
    public function calcFutureMonthYear(int $years, int $month = null)
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

    /**
     * Get info about whether child can defer
     *
     * @return boolean
     */
    public function getCanDeferAttribute()
    {
      // $notices = $this->family->registrations[0]->getEvaluator()->evaluations["App\Child"]["notices"];
      $notices = $this->getEvaluator()->evaluations["App\Child"]["notices"] ?? [];
      // $notices = $this->getEvaluator()->getNoticeReasons();
      \Log::info($notices);

      if (!array_key_exists('ScottishChildCanDefer', $notices)) {
        return false;
      }
      // $rule = [
      //   new Evaluation([
      //         "name" => "ScottishChildCanDefer",
      //         "value" => 0,
      //         "purpose" => "notices",
      //         "entity" => "App\Child",
      //   ])
      // ];
      // $rulesMods = collect($rule);
      // $evaluator = EvaluatorFactory::make($rulesMods);
      $evaluation = $this->getEvaluator()->evaluate($this);
      $notices = $this->family->registrations[0]->getEvaluator()->getNoticeReasons();
      // \Log::info($evaluation);
      \Log::info($notices);
      if (count($notices) > 0) {
        return true;
      }
      return false;
    }
}

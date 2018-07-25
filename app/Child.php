<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{

    // This has a | in the reason field because we want to carry the entity with it.
    const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'child|almost 1 year old'],
        'ChildIsAlmostBorn' => ['reason' => 'child|almost born'],
        'ChildIsOverDue' => ['reason' => 'child|over due date'],
        'ChildIsAlmostSchoolAge' => ['reason' => 'child|almost school age'],
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'ChildIsUnderOne' => ['reason' => 'child|under 1 year old', 'vouchers' => 3],
        'ChildIsUnderSchoolAge' => ['reason' => 'child|under school age', 'vouchers' => 3],
    ];

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
        } else if ($currentDate == $startOfMonth){
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
        return Carbon::createFromDate($school_year, $school_month)->startOfMonth();
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
     * @return array
     */

    public function getStatus()
    {
        $today = Carbon::today();

        $notices = [];
        $credits = [];

        $eligibility = "Ineligible";

        if (!$this->born) {
            // Regardless of age, if you are unborn, you count as a pregnancy and get not credits
            // Even positive ages! This is a process thing

            $eligibility = "Pregnancy";

            // Calculate notices
            /*
             * Remove these Notices for beta;
             * TODO: consider way to re-implement so they only show on Web, not print.
            $is_almost_born = ($today->diffInMonths($this->dob) < 1) && ($this->dob->isFuture());
            $is_overdue = ($today->diffInMonths($this->dob) > 1) && ($this->dob->isPast());

            // Add notices
            ($is_almost_born) ? $notices[] = self::NOTICE_TYPES['ChildIsAlmostBorn'] : false;
            ($is_overdue) ? $notices[] = self::NOTICE_TYPES['ChildIsOverDue'] : false;
            */
        } else {
            // Setup dates
            /** @var Carbon $first_birthday */
            $first_birthday = $this->dob->addYears(1);
            $first_schoolday = $this->calcSchoolStart();

            // Calculate credits
            $is_born = $today->greaterThanOrEqualTo($this->dob);
            $is_one = $today->greaterThanOrEqualTo($first_birthday);
            $is_school_age = $today->greaterThanOrEqualTo($first_schoolday);

            // Calculate notices
            $is_almost_one = ($first_birthday->isFuture() &&
                ($today->diffInMonths($first_birthday) < 1)) ;
            $is_almost_school_age = ($first_schoolday->isFuture() &&
                (($today->diffInMonths($first_schoolday) < 1) ? true : false));

            // Populate notices and credits arrays.
            ($is_almost_one) ? $notices[] = self::NOTICE_TYPES["ChildIsAlmostOne"]: false;
            ($is_almost_school_age) ? $notices[] = self::NOTICE_TYPES['ChildIsAlmostSchoolAge']: false;
            (!$is_one && $is_born) ? $credits[] = self::CREDIT_TYPES["ChildIsUnderOne"]: false;
            (!$is_school_age && $is_born) ? $credits[] = self::CREDIT_TYPES["ChildIsUnderSchoolAge"] : false;

            if (!empty($credits)) {
                $eligibility = 'Eligible';
            }
        }

        return [
            'eligibility' => $eligibility,
            'notices' => $notices,
            'credits' => $credits,
            'vouchers' => array_sum(array_column($credits, "vouchers")),
            ];
    }

    /**
     * Get eligibility value string for Blade.
     *
     * @return mixed|string
     */
    public function getStatusString()
    {
        return $this->getStatus()['eligibility'];
    }

    /**
     * Calculates the entitlement for a child
     *
     * @return int
     */
    public function getEntitlementAttribute()
    {
        return $this->getStatus()['vouchers'];
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

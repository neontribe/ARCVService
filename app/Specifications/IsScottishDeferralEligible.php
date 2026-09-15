<?php

namespace App\Specifications;

use App\Child;
use Carbon\Carbon;
use Chalcedonyt\Specification\AbstractSpecification;

class IsScottishDeferralEligible extends AbstractSpecification
{
    /** @var Carbon $offsetDate */
    protected Carbon $offsetDate;

    /** @var int $schoolMonth */
    private int $schoolMonth;

    /**
     * IsScottishDeferralEligible constructor.
     *
     * @param Carbon|null $offsetDate
     * @param int|null $schoolMonth
     */
    public function __construct(Carbon $offsetDate = null, int $schoolMonth = null)
    {
        $this->offsetDate = $offsetDate ?? Carbon::today()->startOfDay();
        $this->schoolMonth = $schoolMonth ?? (int) config('arc.scottish_school_month', 8);
    }

    /**
     * Tests an object and returns a boolean value
     *
     * @param Child $candidate
     * @return bool
     */
    public function isSatisfiedBy(Child $candidate): bool
    {
        // Children who have already deferred cannot defer again.
        if ($candidate->deferred) {
            return false;
        }

        $birthYear = $candidate->dob->year;
        $birthMonth = $candidate->dob->month;

        // Base/natural start year without deferral
        $startYear = ($birthMonth <= 2) ? $birthYear + 4 : $birthYear + 5;
        $schoolStartDate = Carbon::createFromDate($startYear, $this->schoolMonth, 1)->startOfDay();

        $fifthBirthday = $candidate->dob->copy()->addYears(5)->startOfDay();

        // In Scotland, children who have not reached age 5 by the school start date are eligible to defer.
        return $fifthBirthday->greaterThan($schoolStartDate);
    }
}

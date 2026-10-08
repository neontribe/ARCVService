<?php

namespace Tests\Unit\Specifications;

use App\Child;
use App\Specifications\IsAlmostStartDate;
use Carbon\Carbon;
use Tests\TestCase;

class IsAlmostStartDateTest extends TestCase
{
    /** @test */
    public function it_identifies_a_child_approaching_school_start_date(): void
    {
        // Born 15 May 2021 -> Starts primary school in September 2025 (4 years after birth year for May birth)
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2021-05-15'),
        ]);

        // July 2025: 2 months away -> false
        $specJuly = new IsAlmostStartDate(Carbon::parse('2025-07-15'), 5, 9);
        $this->assertFalse($specJuly->isSatisfiedBy($child));

        // August 2025: 1 month away (next month) -> true
        $specAugust = new IsAlmostStartDate(Carbon::parse('2025-08-15'), 5, 9);
        $this->assertTrue($specAugust->isSatisfiedBy($child));

        // September 2025: school start month (this month) -> true
        $specSeptember = new IsAlmostStartDate(Carbon::parse('2025-09-01'), 5, 9);
        $this->assertTrue($specSeptember->isSatisfiedBy($child));

        // October 2025: past school start month -> false
        $specOctober = new IsAlmostStartDate(Carbon::parse('2025-10-01'), 5, 9);
        $this->assertFalse($specOctober->isSatisfiedBy($child));
    }

    /** @test */
    public function it_handles_year_wrapping_for_custom_january_start_month(): void
    {
        // Born 15 May 2020 -> With offsetMonth 1 (born after Jan), starts January 2025 (5 years ahead)
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-05-15'),
        ]);

        // November 2024: 2 months before -> false
        $specNov = new IsAlmostStartDate(Carbon::parse('2024-11-15'), 5, 1);
        $this->assertFalse($specNov->isSatisfiedBy($child));

        // December 2024: 1 month before January -> true (year wrap)
        $specDec = new IsAlmostStartDate(Carbon::parse('2024-12-15'), 5, 1);
        $this->assertTrue($specDec->isSatisfiedBy($child));

        // January 2025: start month -> true
        $specJan = new IsAlmostStartDate(Carbon::parse('2025-01-10'), 5, 1);
        $this->assertTrue($specJan->isSatisfiedBy($child));

        // February 2025: after start month -> false
        $specFeb = new IsAlmostStartDate(Carbon::parse('2025-02-01'), 5, 1);
        $this->assertFalse($specFeb->isSatisfiedBy($child));
    }
}

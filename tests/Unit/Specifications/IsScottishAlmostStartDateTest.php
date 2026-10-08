<?php

namespace Tests\Unit\Specifications;

use App\Child;
use App\Specifications\IsScottishAlmostStartDate;
use Carbon\Carbon;
use Tests\TestCase;

class IsScottishAlmostStartDateTest extends TestCase
{
    /** @test */
    public function it_identifies_a_child_approaching_august_intake(): void
    {
        // Born January 2021 -> Starts August 2025
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2021-01-15'),
            'deferred' => false,
        ]);

        // June 2025: 2 months away -> not almost start date
        $specJune = new IsScottishAlmostStartDate(Carbon::parse('2025-06-15'));
        $this->assertFalse($specJune->isSatisfiedBy($child));

        // July 2025: 1 month away -> almost start date
        $specJuly = new IsScottishAlmostStartDate(Carbon::parse('2025-07-15'));
        $this->assertTrue($specJuly->isSatisfiedBy($child));

        // August 2025: in start month -> almost start date
        $specAugust = new IsScottishAlmostStartDate(Carbon::parse('2025-08-01'));
        $this->assertTrue($specAugust->isSatisfiedBy($child));

        // September 2025: past start month -> not almost start date
        $specSeptember = new IsScottishAlmostStartDate(Carbon::parse('2025-09-01'));
        $this->assertFalse($specSeptember->isSatisfiedBy($child));
    }

    /** @test */
    public function it_handles_year_wrapping_for_custom_january_start_month(): void
    {
        // Born April 2020 -> Starts January 2025 (if school month is 1)
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-04-10'),
            'deferred' => false,
        ]);

        // November 2024: 2 months before -> false
        $specNov = new IsScottishAlmostStartDate(Carbon::parse('2024-11-15'), 1);
        $this->assertFalse($specNov->isSatisfiedBy($child));

        // December 2024: 1 month before January -> true (year boundary wrap)
        $specDec = new IsScottishAlmostStartDate(Carbon::parse('2024-12-15'), 1);
        $this->assertTrue($specDec->isSatisfiedBy($child));

        // January 2025: start month -> true
        $specJan = new IsScottishAlmostStartDate(Carbon::parse('2025-01-10'), 1);
        $this->assertTrue($specJan->isSatisfiedBy($child));

        // February 2025: after start month -> false
        $specFeb = new IsScottishAlmostStartDate(Carbon::parse('2025-02-01'), 1);
        $this->assertFalse($specFeb->isSatisfiedBy($child));
    }

    /** @test */
    public function it_accounts_for_deferred_children_start_date(): void
    {
        // Born December 2020 -> Base start August 2025, but deferred -> Starts August 2026
        $deferredChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-12-15'),
            'deferred' => true,
        ]);

        // July 2025 (1 month before base start, but child deferred): false
        $specJuly2025 = new IsScottishAlmostStartDate(Carbon::parse('2025-07-15'));
        $this->assertFalse($specJuly2025->isSatisfiedBy($deferredChild));

        // July 2026 (1 month before deferred start): true
        $specJuly2026 = new IsScottishAlmostStartDate(Carbon::parse('2026-07-15'));
        $this->assertTrue($specJuly2026->isSatisfiedBy($deferredChild));
    }
}

<?php

namespace Tests\Unit\Specifications;

use App\Child;
use App\Specifications\IsScottishUnderSchoolAge;
use Carbon\Carbon;
use Tests\TestCase;

class IsScottishUnderSchoolAgeTest extends TestCase
{
    /** @test */
    public function it_identifies_a_baby_as_under_school_age(): void
    {
        $baby = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2024-03-15'),
        ]);

        $spec = new IsScottishUnderSchoolAge(Carbon::parse('2024-09-01'));
        $this->assertTrue($spec->isSatisfiedBy($baby));
    }

    /** @test */
    public function it_identifies_march_born_child_starting_school_in_august_at_age_5(): void
    {
        // Born March 2020 -> Starts August 2025
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-03-15'),
            'deferred' => false,
        ]);

        // July 2025 (age 5y 4m): still under school age
        $specBefore = new IsScottishUnderSchoolAge(Carbon::parse('2025-07-31'));
        $this->assertTrue($specBefore->isSatisfiedBy($child));

        // August 1, 2025: reached school start date
        $specAtStart = new IsScottishUnderSchoolAge(Carbon::parse('2025-08-01'));
        $this->assertFalse($specAtStart->isSatisfiedBy($child));

        // December 2025: already at school
        $specAfter = new IsScottishUnderSchoolAge(Carbon::parse('2025-12-01'));
        $this->assertFalse($specAfter->isSatisfiedBy($child));
    }

    /** @test */
    public function it_identifies_january_born_child_starting_school_in_august_at_age_4_point_5(): void
    {
        // Born January 2021 -> Starts August 2025 (at age 4y 7m)
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2021-01-10'),
            'deferred' => false,
        ]);

        // July 2025: still under school age
        $specBefore = new IsScottishUnderSchoolAge(Carbon::parse('2025-07-31'));
        $this->assertTrue($specBefore->isSatisfiedBy($child));

        // August 2025: starts school
        $specAtStart = new IsScottishUnderSchoolAge(Carbon::parse('2025-08-01'));
        $this->assertFalse($specAtStart->isSatisfiedBy($child));
    }

    /** @test */
    public function it_respects_deferral_for_eligible_children(): void
    {
        // Born December 2020 -> Base start August 2025, but deferred -> Starts August 2026
        $deferredChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-12-15'),
            'deferred' => true,
        ]);

        // August 2025: deferred, so still under school age
        $specAug2025 = new IsScottishUnderSchoolAge(Carbon::parse('2025-08-15'));
        $this->assertTrue($specAug2025->isSatisfiedBy($deferredChild));

        // January 2026 (age 5y 1m): still deferred, so under school age
        $specJan2026 = new IsScottishUnderSchoolAge(Carbon::parse('2026-01-15'));
        $this->assertTrue($specJan2026->isSatisfiedBy($deferredChild));

        // August 2026: now starts school
        $specAug2026 = new IsScottishUnderSchoolAge(Carbon::parse('2026-08-01'));
        $this->assertFalse($specAug2026->isSatisfiedBy($deferredChild));
    }

    /** @test */
    public function it_identifies_older_children_as_not_under_school_age(): void
    {
        $teenager = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2013-05-10'),
        ]);

        $spec = new IsScottishUnderSchoolAge(Carbon::parse('2026-09-15'));
        $this->assertFalse($spec->isSatisfiedBy($teenager));
    }
}

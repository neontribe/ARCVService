<?php

namespace Tests\Unit\Specifications;

use App\Child;
use App\Specifications\IsScottishDeferralEligible;
use Carbon\Carbon;
use Tests\TestCase;

class IsScottishDeferralEligibleTest extends TestCase
{
    /** @test */
    public function it_identifies_children_born_september_through_february_as_deferral_eligible(): void
    {
        // Born Jan 2021 -> Due August 2025 at age 4y 7m (< 5)
        $janChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2021-01-15'),
            'deferred' => false,
        ]);

        // Born Dec 2020 -> Due August 2025 at age 4y 8m (< 5)
        $decChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-12-15'),
            'deferred' => false,
        ]);

        // Born Sept 2020 -> Due August 2025 at age 4y 11m (< 5)
        $septChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-09-01'),
            'deferred' => false,
        ]);

        $spec = new IsScottishDeferralEligible(Carbon::parse('2025-07-15'));
        $this->assertTrue($spec->isSatisfiedBy($janChild));
        $this->assertTrue($spec->isSatisfiedBy($decChild));
        $this->assertTrue($spec->isSatisfiedBy($septChild));
    }

    /** @test */
    public function it_identifies_children_born_march_through_august_as_not_deferral_eligible(): void
    {
        // Born March 2020 -> Due August 2025 at age 5y 5m (>= 5)
        $marchChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-03-15'),
            'deferred' => false,
        ]);

        // Born August 1, 2020 -> Due August 2025 at age 5y 0m (>= 5)
        $augChild = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2020-08-01'),
            'deferred' => false,
        ]);

        $spec = new IsScottishDeferralEligible(Carbon::parse('2025-07-15'));
        $this->assertFalse($spec->isSatisfiedBy($marchChild));
        $this->assertFalse($spec->isSatisfiedBy($augChild));
    }

    /** @test */
    public function it_disallows_deferral_for_already_deferred_children(): void
    {
        $alreadyDeferred = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2021-01-15'),
            'deferred' => true,
        ]);

        $spec = new IsScottishDeferralEligible(Carbon::parse('2025-07-15'));
        $this->assertFalse($spec->isSatisfiedBy($alreadyDeferred));
    }
}

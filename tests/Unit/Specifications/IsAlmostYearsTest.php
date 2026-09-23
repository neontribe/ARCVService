<?php

namespace Tests\Unit\Specifications;

use App\Child;
use App\Specifications\IsAlmostYears;
use Carbon\Carbon;
use Tests\TestCase;

class IsAlmostYearsTest extends TestCase
{
    /** @test */
    public function it_identifies_a_child_approaching_first_birthday(): void
    {
        // Born 15 May 2023 -> 1st birthday is May 2024
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::parse('2023-05-15'),
        ]);

        // March 2024: 2 months away -> false
        $specMarch = new IsAlmostYears(1, Carbon::parse('2024-03-15'));
        $this->assertFalse($specMarch->isSatisfiedBy($child));

        // April 2024: 1 month away (next month) -> true
        $specApril = new IsAlmostYears(1, Carbon::parse('2024-04-15'));
        $this->assertTrue($specApril->isSatisfiedBy($child));

        // May 2024: birthday month (this month) -> true
        $specMay = new IsAlmostYears(1, Carbon::parse('2024-05-01'));
        $this->assertTrue($specMay->isSatisfiedBy($child));

        // June 2024: past birthday month -> false
        $specJune = new IsAlmostYears(1, Carbon::parse('2024-06-01'));
        $this->assertFalse($specJune->isSatisfiedBy($child));
    }
}

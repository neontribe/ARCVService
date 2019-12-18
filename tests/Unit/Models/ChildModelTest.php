<?php

namespace Tests;

use App\Child;
use App\Family;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ChildModelTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function itHasExpectedAttributes()
    {
        $child = factory(Child::class)->make();
        $this->assertNotNull($child->dob);
        $this->assertNotNull($child->born);
        $this->assertNull($child->verfied);
    }

    /** @test */
    public function itCanBeVerified()
    {
        $child1 = factory(Child::class)->states('verified')->make();
        $this->assertNotNull($child1->verified);
        $this->assertTrue($child1->verified);

        $child2 = factory(Child::class)->states('unverified')->make();
        $this->assertNotNull($child2->verified);
        $this->assertFalse($child2->verified);
    }

    /** @test */
    public function itCanHaveAFamily()
    {
        // Make a Family with a Child.
        $family = factory(Family::class)->create();
        $child = factory(Child::class)->make();
        $family->children()->save($child);

        // Check the relationship
        $this->assertNotNull($child->family);
        $this->assertEquals($family->id, $child->family->id);
    }

    /** @test */
    public function itHasAMethodThatCalculatesSchoolAge()
    {
        // Use app.school_month to set the expected "start month".
        $school_month = config('arc.school_month');

        // Create a child born before 1st of app.school_month
        $child = new Child([
            "born" => 'true',
            "dob" => Carbon::createFromDate('2017', ($school_month -1), '1')->toDateTimeString(),
        ]);

        // Check his school month is app.school_month 2021
        $start_school_date = Carbon::createFromDate('2021', $school_month, '1')->toDateString();
        $this->assertEquals($start_school_date, $child->calcSchoolStart()->toDateString());

        // Create a child born after app.school_month 1st
        $child = new Child([
            "born" => 'true',
            "dob" => Carbon::createFromDate('2017', $school_month, '1')->toDateTimeString(),
        ]);

        // Check his school month is app.school_month 2022
        $start_school_date = Carbon::createFromDate('2022', $school_month, '1')->toDateString();
        $this->assertEquals($start_school_date, $child->calcSchoolStart()->toDateString());
    }
}

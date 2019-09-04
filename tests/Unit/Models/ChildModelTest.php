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

    // This has a | in the reason field because we want to carry the entity with it.
    const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'Child|almost 1 year old'],
        'ChildIsAlmostBorn' => ['reason' => 'Child|almost born'],
        'ChildIsOverDue' => ['reason' => 'Child|over due date'],
        'ChildIsAlmostSchoolAge' => ['reason' => 'Child|almost school age'],
        'ChildIsAlmostTwelve' => ['reason' => 'Child|almost 12 years old']
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'ChildIsUnderOne' => ['reason' => 'Child|under 1 year old', 'value' => 3],
        'ChildIsUnderSchoolAge' => ['reason' => 'Child|under school age', 'value' => 3],
        'ChildIsUnderTwelve' => ['reason' => 'Child|under 12 years old', 'value' => 3]
    ];

    /** @test */
    public function itHasExpectedAttributes()
    {
        $child = factory(Child::class)->make();
        $this->assertNotNull($child->dob);
        $this->assertNotNull($child->born);
        $this->assertNotNull($child->entitlement);
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

    /** @test */
    public function itCreditsWhenAChildIsUnderOne()
    {
        // Make a Child under one.
        $child = factory(Child::class, 'underOne')->make();
        $credits = $child->getStatus()['credits'];

        // Check there's two, because child *also* under school age.
        $this->assertEquals(2, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits);
        $this->assertEquals(6, $child->entitlement);
    }

    /** @test */
    public function itCreditsWhenAChildIsUnderSchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'underSchoolAge')->make();
        $credits = $child->getStatus()['credits'];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits, '');
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits, '');
        $this->assertEquals(3, $child->entitlement);
    }

    /** @test */
    public function itDoesNotCreditWhenAChildIsOverSchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'overSchoolAge')->make();
        $credits = $child->getStatus()['credits'];

        // Check there's one, because child is not under one.
        $this->assertEquals(0, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits);
        $this->assertEquals(0, $child->entitlement);
    }

    // Note, we do not test if a child is overdue or almost born.
    // Those rules are deactivated in Child::getStatus()

    /** @test */
    public function itNoticesWhenAChildIsAlmostOne()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'almostOne')->make();
        $notices = $child->getStatus()['notices'];

        // Check there's one, because no other event is pending.
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostSchoolAge'], $notices);
    }

    /** @test */
    public function itNoticesWhenAChildIsAlmostSchoolAge()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.school_month', Carbon::now()->addMonth(1)->month);

        $child = factory(Child::class, 'readyForSchool')->make();
        $notices = $child->getStatus()['notices'];

        // Check there's one, because no other event is pending.
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostSchoolAge'], $notices);
    }


    /** @test */
    public function roundingUpAgeToEndOfMonth()
    {
        // Create a child with a DOB of 12th April 2000
        $dob = Carbon::create(2000, 4, 12, 0, 0, 0, 'Europe/London');
        $child = new Child([
            'born' => true,
            'dob' => $dob,
        ]);

        // Test vouchers if today is the 1st April 2001 / Check is under school age => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 4, 1, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(6, array_sum(array_column($status['credits'], "value")));

        // Test vouchers if today is the 11th April 2001 / Check is under school age => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 4, 11, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(6, array_sum(array_column($status['credits'], "value")));

        // Test vouchers if today is the 12th April 2001 / Check is under school age => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 4, 12, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(6, array_sum(array_column($status['credits'], "value")));

        // Test vouchers if today is the 13th April 2001 / Check is under school age => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 4, 13, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(6, array_sum(array_column($status['credits'], "value")));

        // Test vouchers if today is the 31st April 2001 / Check is under school age => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 4, 30, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(6, array_sum(array_column($status['credits'], "value")));

        // Test vouchers if today is the 5st May 2001 / Check is in school => 3 vouchers
        $status = $child->getStatus(Carbon::create(2001, 5, 5, 0, 0, 0, 'Europe/London'));
        $this->assertEquals(3, array_sum(array_column($status['credits'], "value")));
    }
}

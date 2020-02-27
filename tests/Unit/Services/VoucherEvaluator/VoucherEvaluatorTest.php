<?php

namespace Tests;

use App\Child;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\Valuation;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VoucherEvaluatorTest extends TestCase
{
    use DatabaseMigrations;

    // This has a | in the reason field because we want to carry the entity with it.
    const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'Child|almost 1 year old'],
        'ChildIsAlmostBorn' => ['reason' => 'Child|almost born'],
        'ChildIsOverDue' => ['reason' => 'Child|over due date'],
        'ChildIsAlmostSchoolAge' => ['reason' => 'Child|almost school age'],
        'ChildIsAlmostSecondarySchoolAge' => ['reason' => 'Child|almost secondary school age'],
        'ChildIsAlmostTwelve' => ['reason' => 'Child|almost 12 years old'],
        'ChildIsSchoolAge' => ['reason' => 'Child|school age'],
        'ChildIsSecondarySchoolAge' => ['reason' => 'Child|secondary school age'],
        'FamilyHasUnverifiedChildren' => ['reason' => 'Family|has children needing ID']
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'ChildIsUnderOne' => ['reason' => 'Child|under 1 year old', 'value' => 3],
        'ChildIsUnderSchoolAge' => ['reason' => 'Child|under school age', 'value' => 3],
        'ChildIsUnderTwelve' => ['reason' => 'Child|under 12 years old', 'value' => 3],
        'ChildIsUnderSecondarySchoolAge' => ['reason' => 'Child|under secondary school age', 'value' => 3],
        'FamilyIsPregnant' => ['reason' => 'Family|pregnant', 'value' => 3],
    ];

    /** @test */
    public function itNoticesWhenAFamilyStillRequiresIDForChildren()
    {
        // Create a family with kids that have not been verified
        $family = factory(Family::class)->create();

        $unverifiedKids = factory(Child::class, 3)->states('unverified')->make();
        $family->children()->saveMany($unverifiedKids);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make("extended_age");

        // Evaluate the family
        $evaluation = $evaluator->evaluate($family);
        $notices = $evaluation["notices"];

        // There should be a notice reason of 'FamilyHasUnverifiedChildren'
        $this->assertContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $notices);

        // Set them all verified
        $family->children->each(function ($child) {
            $child->verified = true;
            $child->save();
        });

        // Evaluate the family again.
        $evaluation2 = $evaluator->evaluate($family);
        $notices = $evaluation2["notices"];

        // There should NOT be a notice reason of 'FamilyHasUnverifiedChildren'
        $this->assertNotContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $notices);
    }

    /** @test */
    public function itCreditsWhenAFamilyIsPregnant()
    {
        // Make a pregnant family
        $family = factory(Family::class)->create();

        $pregnancy = factory(Child::class, 'unbornChild')->make();
        $family->children()->save($pregnancy);

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($family);
        $credits = $evaluation["credits"];

        // There should be a credit reason of 'FamilyIsPregnant'
        $this->assertEquals(1, count($credits));
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $credits);
    }

    /** @test */
    public function itDoesNotCreditWhenAFamilyIsIneligible()
    {
        // Make extended evaluator
        $evaluator = EvaluatorFactory::make("extended_age");

        // Make our family
        $family = factory(Family::class)->create();

        // Make a set of ineligible kids (no under school age)
        $overSchool = factory(Child::class, 'overSchoolAge')->make();
        $overSecondarySchool = factory(Child::class, 'overSecondarySchoolAge')->make();

        // Add the kids and check they saved
        $family->children()->saveMany([$overSchool ,$overSecondarySchool]);
        $this->assertEquals(2, $family->children()->count());

        // Run the evaluation
        $evaluation = $evaluator->evaluate($family);
        // Check it can't find any eligible children (0 vouchers)
        // - because no under primary school-ers validate the under secondary school-ers.
        $this->assertFalse($evaluation->getEligibility());
        $this->assertEquals('0', $evaluation->getEntitlement());

        // Add a kid that will make the child under secondary school age.
        $underSchool = factory(Child::class, 'underSchoolAge')->make();

        // Re-save
        $family->children()->saveMany([$underSchool, $overSchool ,$overSecondarySchool]);
        $family = $family->fresh();

        // Check we've saved the children correctly
        $this->assertEquals(3, $family->children->count());

        // Re-evaluate, based on the new reality
        $evaluation = $evaluator->evaluate($family);

        // Check it passes
        $this->assertTrue($evaluation->getEligibility());
        // We have :
        // - one child under school age (3 vouchers)
        // - who enables one child under secondary school age (3 vouchers)
        // - but not one child who is overage (0 vouchers)
        $this->assertEquals('6', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsUnderOne()
    {
        // Make a Child under one.
        $child = factory(Child::class, 'underOne')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's two, because child *also* under school age.
        $this->assertEquals(2, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits);
        $this->assertEquals(6, $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsUnderSchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'underSchoolAge')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits, '');
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits, '');
        $this->assertEquals(3, $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsUnderSecondarySchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'underSchoolAge')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make('extended_age');
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits, '');
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderSecondarySchoolAge'], $credits, '');
        $this->assertEquals(3, $evaluation->getEntitlement());
    }

    /** @test */
    public function itDoesNotCreditWhenAChildIsOverSchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'overSchoolAge')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(0, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderSchoolAge'], $credits);
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

    /** @test */
    public function itDoesNotCreditWhenAChildIsOverSecondarySchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'overSecondarySchoolAge')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make('extended_age');
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(0, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderSecondarySchoolAge'], $credits);
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

    // Note, we do not test if a child is overdue or almost born.
    // Those rules are deactivated in Child::getStatus()

    /** @test */
    public function itNoticesWhenAChildIsAlmostOne()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'almostOne')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $notices = $evaluation["notices"];


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

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $notices = $evaluation["notices"];

        // Check there's one, because no other event is pending.
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostSchoolAge'], $notices);
    }

    /** @test */
    public function itNoticesWhenAChildIsAlmostSecondarySchoolAge()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.school_month', Carbon::now()->addMonth(1)->month);

        $child = factory(Child::class, 'readyForSecondarySchool')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make('extended_age');
        $evaluation = $evaluator->evaluate($child);
        $notices = $evaluation["notices"];

        // Check there's one, because no other event is pending.
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostSecondarySchoolAge'], $notices);
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

        // make a bunch of offset dates to check credits on
        $offsets = [
            '2001-04-01' => 6,
            '2001-04-11' => 6,
            '2001-04-12' => 6,
            '2001-04-13' => 6,
            '2001-04-30' => 6,
            '2001-05-05' => 3
        ];

        foreach ($offsets as $offset => $expected) {
            $offsetDate = Carbon::createFromFormat('Y-m-d', $offset, 'Europe/London');
            // Make a standard valuator
            $evaluator = EvaluatorFactory::make(null, $offsetDate);
            $evaluation = $evaluator->evaluate($child);
            $credits = $evaluation["credits"];
            $this->assertEquals($expected, array_sum(array_column($credits, "value")));
        }
    }
}
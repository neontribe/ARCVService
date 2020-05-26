<?php

namespace Tests;

use App\Child;
use App\Evaluation;
use App\Family;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VoucherEvaluatorTest extends TestCase
{
    use DatabaseMigrations;

    // This has a | in the reason field because we want to carry the entity with it.
    const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'Child|almost 1 year old'],
        'ChildIsAlmostPrimarySchoolAge' => ['reason' => 'Child|almost primary school age'],
        'ChildIsAlmostSecondarySchoolAge' => ['reason' => 'Child|almost secondary school age'],
        'ChildIsPrimarySchoolAge' => ['reason' => 'Child|primary school age'],
        'ChildIsSecondarySchoolAge' => ['reason' => 'Child|secondary school age'],
        'FamilyHasUnverifiedChildren' => ['reason' => 'Family|has one or more children that you haven\'t checked ID for yet']
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'ChildIsUnderOne' => ['reason' => 'Child|under 1 year old', 'value' => 6],
        'ChildIsBetweenOneAndPrimarySchoolAge' => ['reason' => 'Child|between 1 and primary school age', 'value' => 3],
        'ChildIsPrimarySchoolAge' => ['reason' => 'Child|primary school age', 'value' => 3],
        'FamilyIsPregnant' => ['reason' => 'Family|pregnant', 'value' => 3],
    ];

    private $rulesMods = [];

    protected function setUp()
    {
        parent::setUp();

        // Changes for "extended age".
        $this->rulesMods["credit-extended"] = [
            // warn when primary schoolers are approaching end of school
            new Evaluation([
                "name" => "ChildIsAlmostSecondarySchoolAge",
                "value" => "0",
                "purpose" => "notice",
                "entity" => "App\Child",
            ]),
            // credit primary schoolers
            new Evaluation([
               "name" => "ChildIsPrimarySchoolAge",
               "value" => "3",
               "purpose" => "credits",
               "entity" => "App\Child",
            ]),
            // don't disqualify primary schoolers
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ]),
            // do secondary schoolers instead
            new Evaluation([
                "name" => "ChildIsSecondarySchoolAge",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ])
        ];

        // this one is a decoration of the above
        $this->rulesMods["credit-extended-qualified"] = array_merge(
            $this->rulesMods["credit-extended"],
            [
                // Turn on disqualifier
                new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
                ]),
            ]
        );

        //dd($this->rulesMods["credit-extended-qualified"]);

        // This one can be standalone; combine with others in test
        $this->rulesMods["notice-unverified-kids"] = [
            // Turn on notice
            new Evaluation([
                "name" => "FamilyHasUnverifiedChildren",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ]),
        ];
    }

    /** @test */
    public function itNoticesWhenAFamilyStillRequiresIDForChildren()
    {
        // Create a family with kids that have not been verified
        $family = factory(Family::class)->create();

        $unverifiedKids = factory(Child::class, 3)->states('unverified')->make();
        $family->children()->saveMany($unverifiedKids);

        // Get rules Mods
        $rulesMods = collect($this->rulesMods["notice-unverified-kids"]);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);

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
    public function itCreditsQualifiedPrimarySchoolChildrenButNotUnqualifiedOnes()
    {
        // get rules mods
        $rulesMods = collect($this->rulesMods["credit-extended-qualified"]);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);

        // Make our family
        $family = factory(Family::class)->create();

        // Make a set of ineligible kids (no under primary school age)
        $isPrimarySchool = factory(Child::class, 'isPrimarySchoolAge')->make();
        $isOverPrimarySchool = factory(Child::class, 'isOverPrimarySchoolAge')->make();

        // Add the kids and check they saved
        $family->children()->saveMany([$isPrimarySchool ,$isOverPrimarySchool]);
        $this->assertEquals(2, $family->children()->count());

        // Run the evaluation
        $evaluation = $evaluator->evaluate($family);
        // Check it can't find any eligible children (0 vouchers)
        // - because no under primary school-ers validate the primary school-ers.
        $this->assertFalse($evaluation->getEligibility());
        $this->assertEquals('0', $evaluation->getEntitlement());

        // Add a kid that will make the child at primary school age qualified.
        $underPrimarySchool = factory(Child::class, 'betweenOneAndPrimarySchoolAge')->make();

        // Re-save
        $family->children()->saveMany([$underPrimarySchool, $isPrimarySchool ,$isOverPrimarySchool]);
        $family = $family->fresh();

        // Check we've saved the children correctly
        $this->assertEquals(3, $family->children->count());

        // Re-evaluate, based on the new reality
        $evaluation = $evaluator->evaluate($family);

        // Check it passes
        $this->assertTrue($evaluation->getEligibility());
        // We have :
        // - one child between 1 and primary school age (3 vouchers)
        // - who enables one child at primary school age (3 vouchers)
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

        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertEquals(6, $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsBetweenOneAndPrimarySchoolAge()
    {
        // Make a Child under School Age.
        $child = factory(Child::class, 'betweenOneAndPrimarySchoolAge')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsBetweenOneAndPrimarySchoolAge'], $credits, '');
        $this->assertEquals(3, $evaluation->getEntitlement());
    }

    /** @test */
    public function itDoesNotCreditWhenAChildIsOverPrimarySchoolAge()
    {
        // Make a Secondary school child
        $child = factory(Child::class, 'isOverPrimarySchoolAge')->make();

        $rulesMod = collect($this->rulesMods["credit-extended"]);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($child);
        $credits = $evaluation["credits"];

        // Check there's none, because child is not primary or under.
        $this->assertEquals(0, count($credits));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsBetweenOneAndPrimarySchoolAge'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['ChildIsPrimarySchoolAge'], $credits);
        $this->assertNotContains(self::CREDIT_TYPES['FamilyIsPregnant'], $credits);
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

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
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostPrimarySchoolAge'], $notices);
    }

    /** @test */
    public function itNoticesWhenAChildIsAlmostPrimarySchoolAge()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.school_month', Carbon::now()->addMonth(1)->month);

        $child = factory(Child::class, 'readyForPrimarySchool')->make();

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);
        $notices = $evaluation["notices"];

        // Check there's one, because no other event is pending.
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostPrimarySchoolAge'], $notices);
    }

    /** @test */
    public function itNoticesWhenAChildIsAlmostSecondarySchoolAge()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.school_month', Carbon::now()->addMonth(1)->month);

        $child = factory(Child::class, 'readyForSecondarySchool')->make();

        // Get Rules Mods
        $rulesMod = collect($this->rulesMods["credit-extended"]);

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($child);
        $notices = $evaluation["notices"];

        // Check there's one
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

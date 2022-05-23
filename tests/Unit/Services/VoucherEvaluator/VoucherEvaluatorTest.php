<?php

namespace Tests\Unit\Services\VoucherEvaluator;

use App\Centre;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

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
        'ChildIsBetweenOneAndPrimarySchoolAge' => ['reason' => 'Child|between 1 and start of primary school age', 'value' => 3],
        'ChildIsPrimarySchoolAge' => ['reason' => 'Child|primary school age', 'value' => 3],
        'FamilyIsPregnant' => ['reason' => 'Family|pregnant', 'value' => 3],
    ];

    private $rulesMods = [];

    private $family;
    private $pregnancy;
    private $isPrimarySchool;
    private $isOverPrimarySchool;
    private $underPrimarySchool;
    private $underOne;
    private $isSecondarySchoolAge;
    private $isAlmostOne;
    private $readyForPrimarySchool;
    private $readyForSecondarySchool;

    protected function setUp(): void
    {
        parent::setUp();

        // Changes for "extended age".
        $this->rulesMods["credit-primary"] = [
            // warn when primary schoolers are approaching end of school
            new Evaluation([
                "name" => "ChildIsAlmostSecondarySchoolAge",
                "value" => "0",
                "purpose" => "notices",
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
        $this->rulesMods["credit-primary-qualified"] = array_merge(
            $this->rulesMods["credit-primary"],
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

        $this->family = factory(Family::class)->create();
        $this->pregnancy = factory(Child::class, 'unbornChild')->make();
        $this->isPrimarySchool = factory(Child::class, 'isPrimarySchoolAge')->make();
        $this->isOverPrimarySchool = factory(Child::class, 'isSecondarySchoolAge')->make();
        $this->underPrimarySchool = factory(Child::class, 'betweenOneAndPrimarySchoolAge')->make();
        $this->underOne = factory(Child::class, 'underOne')->make();
        $this->isSecondarySchoolAge = factory(Child::class, 'isSecondarySchoolAge')->make();
        $this->isAlmostOne = factory(Child::class, 'almostOne')->make();
        $this->readyForPrimarySchool = factory(Child::class, 'readyForPrimarySchool')->make();
        $this->readyForSecondarySchool = factory(Child::class, 'readyForSecondarySchool')->make();
    }

    /** @test */
    public function itNoticesWhenAFamilyStillRequiresIDForChildren()
    {
        $unverifiedKids = factory(Child::class, 3)->states('unverified')->make();
        $this->family->children()->saveMany($unverifiedKids);

        // Get rules Mods
        $rulesMods = collect($this->rulesMods["notice-unverified-kids"]);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);

        // Evaluate the family
        $evaluation = $evaluator->evaluate($this->family);
        $notices = $evaluation["notices"];

        // There should be a notice reason of 'FamilyHasUnverifiedChildren'
        $this->assertContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $notices);

        // Set them all verified
        $this->family->children->each(function ($child) {
            $child->verified = true;
            $child->save();
        });

        // Evaluate the family again.
        $evaluation2 = $evaluator->evaluate($this->family);
        $notices = $evaluation2["notices"];

        // There should NOT be a notice reason of 'FamilyHasUnverifiedChildren'
        $this->assertNotContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $notices);
    }

    /** @test */
    public function itCreditsWhenAFamilyIsPregnant()
    {
        $this->family->children()->save($this->pregnancy);

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->family);
        $credits = $evaluation["credits"];

        // There should be a credit reason of 'FamilyIsPregnant'
        $this->assertEquals(1, count($credits));
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $credits);
    }

    /** @test */
    public function itCreditsUnrestrictedPrimarySchoolChildren()
    {
        // get rules mods
        $rulesMods = collect($this->rulesMods["credit-primary"]);

        // Make evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);

        // Add the kids and check they saved
        $this->family->children()->saveMany([$this->isPrimarySchool, $this->isOverPrimarySchool]);
        $this->assertEquals(2, $this->family->children()->count());

        $evaluation = $evaluator->evaluate($this->family);

        // Check it can find eligible children (0 vouchers)
        // - because no under primary school-ers validate the primary school-ers.
        $this->assertTrue($evaluation->getEligibility());
        $this->assertEquals('3', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsQualifiedPrimarySchoolChildrenButNotUnqualifiedOnes()
    {
        // get rules mods
        $rulesMods = collect($this->rulesMods["credit-primary-qualified"]);

        // Make evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);

        // Add a set of ineligible kids (no under primary school age qualifiers) and check they saved
        $this->family->children()->saveMany([$this->isPrimarySchool, $this->isOverPrimarySchool]);
        $this->assertEquals(2, $this->family->children()->count());

        // Run the evaluation
        $evaluation = $evaluator->evaluate($this->family);
        // Check it can't find any eligible children (0 vouchers)
        // - because no under primary school-ers validate the primary school-ers.
        $this->assertFalse($evaluation->getEligibility());
        $this->assertEquals('0', $evaluation->getEntitlement());

        // Re-save with a kid that will make the child at primary school age qualified
        $this->family->children()->saveMany([$this->underPrimarySchool, $this->isPrimarySchool, $this->isOverPrimarySchool]);
        $family = $this->family->fresh();

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
        // Make standard evaluator for a child under one
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->underOne);
        $credits = $evaluation["credits"];

        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsUnderOne'], $credits);
        $this->assertEquals(6, $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsBetweenOneAndPrimarySchoolAge()
    {
        // Make standard evaluator for a child under school age
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->underPrimarySchool);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsBetweenOneAndPrimarySchoolAge'], $credits);
        $this->assertEquals(3, $evaluation->getEntitlement());
    }

    /** @test */
    public function itDoesNotCreditWhenAChildisSecondarySchoolAge()
    {
        $rulesMod = collect($this->rulesMods["credit-primary"]);

        // Make extended evaluator
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($this->isSecondarySchoolAge);
        $credits = $evaluation["credits"];

        // Check there's none, because child is not primary or under.
        $this->assertEquals(0, count($credits));
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

    /** @test */
    public function itNoticesWhenAChildIsAlmostOne()
    {
        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->isAlmostOne);
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
        Config::set('arc.school_month', Carbon::now()->addMonth()->month);

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->readyForPrimarySchool);
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
        Config::set('arc.school_month', Carbon::now()->addMonth()->month);

        // Get Rules Mods
        $rulesMod = collect($this->rulesMods["credit-primary"]);

        // Make standard evaluator
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($this->readyForSecondarySchool);
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

    /** @test */
    public function itHasADefaultSetOfRulesAndCanAcceptVariableValuesForEvaluations()
    {
        // We make a registration in a non-SK area
        $centre = factory(Centre::class)->create();

        $registration = new Registration();
        $registration->centre_id = $centre->id;
        $registration->eligibility_hsbs = "healthy-start-applying";
        $registration->eligibility_nrpf = "no";
        $registration->family_id = $this->family->id;
        $registration->save();

        // Add some children to the family
        $children = [$this->pregnancy, $this->underOne, $this->underPrimarySchool, $this->isPrimarySchool];

        $this->family->children()->saveMany($children);
        // Test family has 4 children, including a pregnancy
        $this->assertEquals(4, $this->family->children->count());

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($this->family);

        // Test we get a default number of total vouchers
        $this->assertEquals(12, $evaluation->getEntitlement());

        // Change number of vouchers allocated to a pregnant family
        $this->rulesMods['pregnancy'] = [
            new Evaluation([
            'name' => 'FamilyIsPregnant',
            'value' => 4,
            'purpose' => 'credits',
            'entity' => 'App\Family',
            'sponsor_id' => $registration->centre->sponsor->id,
            ])
        ];

        $evaluator = EvaluatorFactory::make(collect($this->rulesMods['pregnancy']));
        $newFamilyEvaluation = $evaluator->evaluate($this->family);
        $credits = $newFamilyEvaluation['credits'];
        // We test we get more vouchers with the new rule
        $this->assertNotEquals(self::CREDIT_TYPES['FamilyIsPregnant']['value'], $credits[0]['value']);
        $this->assertEquals(4, $credits[0]['value']);
        $this->assertEquals(13, $newFamilyEvaluation->getEntitlement());
    }
}

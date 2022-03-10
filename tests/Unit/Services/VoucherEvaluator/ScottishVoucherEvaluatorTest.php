<?php

namespace Tests\Unit\Services\VoucherEvaluator;

use App\Centre;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ScottishVoucherEvaluatorTest extends TestCase
{
    use DatabaseMigrations;

    // This has a | in the reason field because we want to carry the entity with it.
    const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'Child|is almost 1 year old'],
        'ScottishChildIsAlmostPrimarySchoolAge' => ['reason' => 'Child|is almost primary school age (SCOTLAND)'],
        'ChildIsAlmostSecondarySchoolAge' => ['reason' => 'Child|is almost secondary school age'],
        'ChildIsPrimarySchoolAge' => ['reason' => 'Child|is primary school age (SCOTLAND)'],
        'ChildIsSecondarySchoolAge' => ['reason' => 'Child|is secondary school age'],
        'FamilyHasUnverifiedChildren' => ['reason' => 'Family|has one or more children that you haven\'t checked ID for yet'],
        'ScottishChildCanDefer' => ['reason' => 'Child|is able to defer (SCOTLAND)'],
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'ChildIsUnderOne' => ['reason' => 'Child|is under 1 year old', 'value' => 6],
        'ChildIsBetweenOneAndPrimarySchoolAge' => ['reason' => 'Child|is between 1 and start of primary school age', 'value' => 4],
        'ChildIsPrimarySchoolAge' => ['reason' => 'Child|is primary school age', 'value' => 4],
        'FamilyIsPregnant' => ['reason' => 'Family|is pregnant', 'value' => 4],
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
    private $readyForScottishPrimarySchool;
    private $readyForSecondarySchool;
    private $canDefer;

    protected function setUp(): void
    {
        parent::setUp();

        // Changes for "extended age".
        $this->rulesMods["credit-primary"] = [
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ScottishChildIsPrimarySchoolAge",
                "value" => "4",
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
            ]),
            new Evaluation([
                    "name" => "ChildIsAlmostPrimarySchoolAge",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ScottishChildIsAlmostPrimarySchoolAge",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ChildIsAlmostSecondarySchoolAge",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ScottishChildCanDefer",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ChildIsSecondarySchoolAge",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Child",
            ]),
        ];

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
        $this->readyForScottishPrimarySchool = factory(Child::class, 'readyForScottishPrimarySchool')->make();
        $this->readyForSecondarySchool = factory(Child::class, 'readyForSecondarySchool')->make();
        $this->canDefer = factory(Child::class, 'canDefer')->make();
        $this->canNotDefer = factory(Child::class, 'canNotDefer')->make();
    }

    /** @test */
    public function itCreditsWhenAFamilyIsPregnant()
    {
        $this->family->children()->save($this->pregnancy);

        // Make standard evaluator
        $rulesMods = collect($this->rulesMods["credit-primary"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->family);
        $credits = $evaluation["credits"];

        // There should be a credit reason of 'FamilyIsPregnant'
        $this->assertEquals(1, count($credits));
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $credits);
    }

    /** @test */
    public function itDoesntCreditPrimarySchoolChildren()
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
        $this->assertFalse($evaluation->getEligibility());
        $this->assertEquals('0', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsQualifiedPrimarySchoolChildrenButNotUnqualifiedOnes()
    {
        $rulesMods = collect($this->rulesMods["credit-primary"]);
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
\Log::info('$this->underPrimarySchool ' . $this->underPrimarySchool->dob);
\Log::info('$this->isPrimarySchool ' . $this->isPrimarySchool->dob);
\Log::info('$this->isOverPrimarySchool ' . $this->isOverPrimarySchool->dob);
        // Check we've saved the children correctly
        $this->assertEquals(3, $family->children->count());

        // Re-evaluate, based on the new reality
        $evaluation = $evaluator->evaluate($family);

        // Check it passes
        $this->assertTrue($evaluation->getEligibility());
        // We have :
        // - one child between 1 and primary school age (4 vouchers)
        // - who enables one child at primary school age (4 vouchers)
        // - and one child who is overage (0 vouchers)

        $this->assertEquals('8', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAChildIsBetweenOneAndPrimarySchoolAge()
    {
        $rulesMods = collect($this->rulesMods["credit-primary"]);
        // Make evaluator
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->underPrimarySchool);
        $credits = $evaluation["credits"];

        // Check there's one, because child is not under one.
        $this->assertEquals(1, count($credits));

        // Check the correct credit type is applied.
        $this->assertContains(self::CREDIT_TYPES['ChildIsBetweenOneAndPrimarySchoolAge'], $credits);
        $this->assertEquals(4, $evaluation->getEntitlement());
    }


    /** @test */
    public function itNoticesWhenAChildIsAlmostPrimarySchoolAge()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.scottish_school_month', Carbon::now()->addMonth()->month);

        $rulesMod = collect($this->rulesMods["credit-primary"]);
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($this->readyForScottishPrimarySchool);
        $notices = $evaluation["notices"];

        // Check there's one, because no other event is pending.
        $this->assertEquals(2, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ScottishChildIsAlmostPrimarySchoolAge'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ScottishChildCanDefer'], $notices);
    }

    /** @test */
    public function itNoticesWhenAChildCanDefer()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.scottish_school_month', Carbon::now()->addMonth()->month);

        $rulesMod = collect($this->rulesMods["credit-primary"]);
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($this->canDefer);
        $notices = $evaluation["notices"];

        // Check there's one
        $this->assertEquals(2, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ScottishChildCanDefer'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ScottishChildIsAlmostPrimarySchoolAge'], $notices);
    }

    /** @test */
    public function itWontDeferAChildWhoIsOverFourAtSchoolStart()
    {
        // Need to change the values we use for school start to next month's integer
        Config::set('arc.scottish_school_month', Carbon::now()->addMonth()->month);

        $rulesMod = collect($this->rulesMods["credit-primary"]);
        $evaluator = EvaluatorFactory::make($rulesMod);
        $evaluation = $evaluator->evaluate($this->canNotDefer);
        $notices = $evaluation["notices"];

        // Check there's one
        $this->assertEquals(1, count($notices));

        // Check the correct credit type is applied.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $notices);
        $this->assertNotContains(self::NOTICE_TYPES['ScottishChildCanDefer'], $notices);
        $this->assertContains(self::NOTICE_TYPES['ScottishChildIsAlmostPrimarySchoolAge'], $notices);
    }
}

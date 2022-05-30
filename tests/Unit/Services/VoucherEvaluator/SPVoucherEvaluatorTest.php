<?php

namespace Tests\Unit\Services\VoucherEvaluator;

use App\Centre;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SPVoucherEvaluatorTest extends TestCase
{
    use DatabaseMigrations;

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'HouseholdExists' => ['reason' => 'Family|exists', 'value' => 10],
        'HouseholdMember' => ['reason' => 'Child|is member of the household', 'value' => 7],
        'DeductFromCarer' => ['reason' => 'Child|', 'value' => -7],
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
    private $readyForSecondarySchool;
    private $readyForPrimarySchool;

    protected function setUp(): void
    {
        parent::setUp();

        // Changes for "extended age".
        $this->rulesMods["credit-sp"] = [
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsUnderOne",
                "value" => null,
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
                "name" => "DeductFromCarer",
                "value" => -7,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "HouseholdMember",
                "value" => 7,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "HouseholdExists",
                "value" => 10,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                    "name" => "ChildIsAlmostPrimarySchoolAge",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ChildIsAlmostOne",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
        ];

        $this->sponsor = factory(Sponsor::class)->create([
            'programme' => 1
        ]);
        $this->centre = factory(Centre::class)->create([
            'sponsor_id' => $this->sponsor->id
        ]);
        $this->family = factory(Family::class)->create([
            'initial_centre_id' => $this->centre->id
        ]);

        // Add a 'child' to represent the first carer
        $this->carer = factory(Child::class)->create([
            'dob' => '2000-01-01',
            'family_id' => $this->family->id,
            'born' => 1
        ]);
        // $this->pregnancy = factory(Child::class, 'unbornChild')->make();
        $this->isPrimarySchool = factory(Child::class, 'isPrimarySchoolAge')->make();
        // $this->isOverPrimarySchool = factory(Child::class, 'isSecondarySchoolAge')->make();
        // $this->underPrimarySchool = factory(Child::class, 'betweenOneAndPrimarySchoolAge')->make();
        $this->underOne = factory(Child::class, 'underOne')->make();
        $this->readyForPrimarySchool = factory(Child::class, 'readyForPrimarySchool')->make();

        // $this->isSecondarySchoolAge = factory(Child::class, 'isSecondarySchoolAge')->make();
        $this->isAlmostOne = factory(Child::class, 'almostOne')->make();
        // $this->readyForSecondarySchool = factory(Child::class, 'readyForSecondarySchool')->make();
    }

    /** @test */
    public function itCreditsWhenAHouseholdExists()
    {
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->family);
        $credits = $evaluation["credits"];
        $this->assertEquals(1, count($credits));
        $this->assertContains(self::CREDIT_TYPES['HouseholdExists'], $credits);
        $this->assertEquals('10', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenAHouseholdMemberExists()
    {
        $this->family->children()->save($this->isPrimarySchool);
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->family);
        $this->assertEquals(2, $this->family->children()->count());
        $this->assertEquals('17', $evaluation->getEntitlement());
    }

    /** @test */
    public function itCreditsWhenMultipleHouseholdMembersExist()
    {
        $this->family->children()->saveMany([$this->isPrimarySchool, $this->underOne]);
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->family);
        $this->assertEquals(3, $this->family->children()->count());
        $this->assertEquals('24', $evaluation->getEntitlement());
    }

    /** @test */
    public function socialPrescriptionUsersDontSeeNoticesForPrimary()
    {
        Config::set('arc.school_month', Carbon::now()->addMonth()->month);
        $this->family->children()->save($this->readyForPrimarySchool);
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->readyForPrimarySchool);
        $notices = $evaluation["notices"];
        $this->assertEquals(0, count($notices));
    }

    /** @test */
    public function socialPrescriptionUsersDontSeeNoticesForChildIsAlmostOne()
    {
        $this->family->children()->save($this->isAlmostOne);
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->isAlmostOne);
        $notices = $evaluation["notices"];
        $this->assertEquals(0, count($notices));
    }

    /** @test */
    public function itIsCorrectlyDeductingCreditsForCarer()
    {
        $this->family->children()->saveMany([$this->isPrimarySchool, $this->underOne]);
        $rulesMods = collect($this->rulesMods["credit-sp"]);
        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($this->carer);
        $credits = $evaluation["credits"];
        $this->assertContains(self::CREDIT_TYPES['DeductFromCarer'], $credits);
        $this->assertEquals('0', $evaluation->getEntitlement());
        
        $evaluator2 = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator2->evaluate($this->family);
        $this->assertEquals('24', $evaluation->getEntitlement());
    }
}

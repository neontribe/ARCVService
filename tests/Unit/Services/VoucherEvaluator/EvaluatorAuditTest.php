<?php

namespace Tests\Unit\Services\VoucherEvaluator;

use App\Child;
use App\Evaluation;
use App\Family;
use App\Services\VoucherEvaluator\Evaluations\ScottishFamilyHasNoEligibleChildren;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Reproduction tests for the findings in docs/VOUCHER_EVALUATOR_AUDIT.md.
 *
 * Each test asserts the CURRENT, BUGGY behaviour, and is skipped so CI stays
 * green (matching the former 'Waiting for hotfix' convention in
 * ScottishVoucherEvaluatorTest). When a finding is fixed, remove the
 * markTestSkipped() line and invert the buggy assertions to get a regression test.
 *
 * F8 and F10 were fixed by the Scottish specification refactor (commits
 * 49ed1f98 and 2882057f); their tests below are now live regression tests.
 */
class EvaluatorAuditTest extends TestCase
{
    use RefreshDatabase;

    // This has a | in the reason field because we want to carry the entity with it.
    public const NOTICE_TYPES = [
        'ChildIsAlmostOne' => ['reason' => 'Child|almost 1 year old'],
        'FamilyHasUnverifiedChildren' => ['reason' => 'Family|has one or more children that you haven\'t checked ID for yet'],
    ];

    // This has a | in the reason field because we want to carry the entity with it.
    public const CREDIT_TYPES = [
        'FamilyIsPregnant' => ['reason' => 'Family|pregnant', 'value' => 4],
        'ScottishChildIsBetweenOneAndPrimarySchoolAge' => ['reason' => 'Child|between 1 and start of primary school age (SCOTLAND)', 'value' => 4],
        'HouseholdExists' => ['reason' => 'Family|exists', 'value' => 7],
        'HouseholdMember' => ['reason' => 'Child|member of the household', 'value' => 7],
        'DeductFromCarer' => ['reason' => 'Family|', 'value' => -7],
    ];

    /**
     * Social prescribing rule mods, as per config/evaluations.php.
     *
     * @return \Illuminate\Support\Collection
     */
    private function socialPrescribingMods()
    {
        return collect([
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
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "HouseholdMember",
                "value" => 7,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "HouseholdExists",
                "value" => 7,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsAlmostPrimarySchoolAge",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsAlmostOne",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ]),
        ]);
    }

    /**
     * Scottish rule mods (subset of config/evaluations.php sufficient for a Child).
     *
     * @return \Illuminate\Support\Collection
     */
    private function scottishChildMods()
    {
        return collect([
            new Evaluation([
                "name" => "ScottishChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
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
                "name" => "ChildIsAlmostPrimarySchoolAge",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ]),
        ]);
    }

    /**
     * F1: a disqualified child's reason is present in the raw 'disqualifiers'
     * bucket but absent from getNoticeReasons(), because Valuation.php:47 merges
     * the non-existent 'disqualifications' key instead of 'disqualifiers'.
     */
    public function testAuditF1DisqualifierReasonsAreLostFromNoticeReasons(): void
    {
        $this->markTestSkipped('AUDIT F1 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        // A six-year-old: disqualified by ChildIsPrimarySchoolAge, no notices due.
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::now()->startOfMonth()->subYears(6)->toDateTimeString(),
        ]);

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);

        // The raw bucket has the reason...
        $this->assertContains(['reason' => 'Child|primary school age'], $evaluation["disqualifiers"]);

        // BUG: ...but getNoticeReasons() loses it entirely.
        $this->assertEquals([], $evaluation->getNoticeReasons());
    }

    /**
     * F2: the 'almost 1' notice fires when the milestone is still nearly two
     * months away, because (int) diffInMonths(...) <= 1 truncates Carbon 3's
     * signed float.
     */
    public function testAuditF2AlmostNoticeFiresAlmostTwoMonthsEarly(): void
    {
        $this->markTestSkipped('AUDIT F2 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $offsetDate = Carbon::now()->startOfMonth();
        // First birthday falls next month; IsAlmostYears targets the END of that
        // month, ~2 months after the offset date.
        $dob = Carbon::now()->startOfMonth()->addMonthsNoOverflow(1)->subYears(1);
        $targetDate = $dob->copy()->endOfMonth()->addYears(1);

        // The milestone is comfortably more than one month out...
        $this->assertGreaterThanOrEqual(55, $offsetDate->diffInDays($targetDate));

        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => $dob->toDateTimeString(),
        ]);

        $evaluator = EvaluatorFactory::make(null, $offsetDate);
        $evaluation = $evaluator->evaluate($child);

        // BUG: ...yet the notice already fires.
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $evaluation["notices"]);
    }

    /**
     * F3: an unborn child whose due date is months in the past still earns the
     * FamilyIsPregnant credit, because Family::getExpectingAttribute() never
     * compares the dob with today.
     */
    public function testAuditF3PregnancyCreditSurvivesItsDueDate(): void
    {
        $this->markTestSkipped('AUDIT F3 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $family = factory(Family::class)->create();
        $overduePregnancy = factory(Child::class)->make([
            'born' => false,
            'dob' => Carbon::now()->startOfMonth()->subMonths(3)->toDateTimeString(),
        ]);
        $family->children()->save($overduePregnancy);

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($family->fresh());

        // BUG: the due date is three months gone, but the credit persists...
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $evaluation["credits"]);
        // ...and the "baby" earns nothing (every child credit requires IsBorn).
        $this->assertEquals(4, $evaluation->getEntitlement());
    }

    /**
     * F3: a twin pregnancy (two unborn records) credits once, because
     * getExpectingAttribute() overwrites rather than counts.
     */
    public function testAuditF3TwinPregnancyCreditsOnce(): void
    {
        $this->markTestSkipped('AUDIT F3 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $family = factory(Family::class)->create();
        $twins = factory(Child::class, 2)->states('unbornChild')->make();
        $family->children()->saveMany($twins);

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($family->fresh());

        $pregnancyCredits = array_filter(
            $evaluation["credits"],
            function ($credit) {
                return $credit['reason'] === 'Family|pregnant';
            }
        );

        // BUG: two pregnancies, one credit.
        $this->assertCount(1, $pregnancyCredits);
        $this->assertEquals(4, $evaluation->getEntitlement());
    }

    /**
     * F4: an unborn child can never be ID-verified, but is still counted by
     * FamilyHasUnverifiedChildren — so a pregnant family with all born children
     * verified shows an un-clearable "needs ID" notice.
     */
    public function testAuditF4UnbornChildTriggersUnverifiedNotice(): void
    {
        $this->markTestSkipped('AUDIT F4 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $family = factory(Family::class)->create();
        $bornAndVerified = factory(Child::class)->states('betweenOneAndPrimarySchoolAge', 'verified')->make();
        $unborn = factory(Child::class)->states('unbornChild', 'unverified')->make();
        $family->children()->saveMany([$bornAndVerified, $unborn]);

        $rulesMods = collect([
            new Evaluation([
                "name" => "FamilyHasUnverifiedChildren",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ]),
        ]);

        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($family->fresh());

        // BUG: every born child is verified, yet the notice fires for the pregnancy.
        $this->assertContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $evaluation["notices"]);
    }

    /**
     * F5: HouseholdMember (a Child evaluation) tests leaving_on/rejoin_on, which
     * only exist on the families table — so children of a household that has
     * left still earn their member credit.
     */
    public function testAuditF5DepartedHouseholdStillCreditsMembers(): void
    {
        $this->markTestSkipped('AUDIT F5 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $family = factory(Family::class)->create();
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->save();

        $child = factory(Child::class)->states('betweenOneAndPrimarySchoolAge')->make();
        $family->children()->save($child);

        $evaluator = EvaluatorFactory::make($this->socialPrescribingMods());
        $evaluation = $evaluator->evaluate($family->fresh());

        $allCredits = $evaluation->flat("credits");

        // The family-level rule correctly notices the departure...
        $this->assertNotContains(self::CREDIT_TYPES['HouseholdExists'], $allCredits);
        // BUG: ...but the child still earns its 7-voucher member credit.
        $this->assertContains(self::CREDIT_TYPES['HouseholdMember'], $allCredits);
    }

    /**
     * F6: getEntitlement() has no floor, so a departed social-prescribing family
     * with no children computes a negative entitlement (DeductFromCarer always
     * fires — see F7).
     */
    public function testAuditF6EntitlementCanGoNegative(): void
    {
        $this->markTestSkipped('AUDIT F6 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $family = factory(Family::class)->create();
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->save();

        $evaluator = EvaluatorFactory::make($this->socialPrescribingMods());
        $evaluation = $evaluator->evaluate($family->fresh());

        // BUG: -7 — HouseholdExists fails, but the carer deduction still applies.
        $this->assertEquals(-7, $evaluation->getEntitlement());
    }

    /**
     * F8 (FIXED — regression test): the Scottish rules used to read
     * Carbon::now() instead of the injected offsetDate, so evaluating four
     * years in the future returned exactly today's answer. Since the
     * specification refactor (IsScottishUnderSchoolAge et al.) the offsetDate
     * is honoured: a toddler credited today loses the credit when evaluated
     * six years on, by which time they are at school.
     */
    public function testAuditF8ScottishRulesRespectOffsetDate(): void
    {
        // A toddler: credited by ScottishChildIsBetweenOneAndPrimarySchoolAge today.
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::now()->startOfMonth()->subMonths(24)->toDateTimeString(),
            'deferred' => false,
        ]);

        $evaluationNow = EvaluatorFactory::make($this->scottishChildMods())
            ->evaluate($child);
        $evaluationFuture = EvaluatorFactory::make($this->scottishChildMods(), Carbon::now()->addYears(6))
            ->evaluate($child);

        // Sanity: the credit applies today.
        $this->assertContains(
            self::CREDIT_TYPES['ScottishChildIsBetweenOneAndPrimarySchoolAge'],
            $evaluationNow["credits"]
        );

        // FIXED: six years on the child is ~8 and at school — the offsetDate is
        // consulted and the credit no longer applies.
        $this->assertNotContains(
            self::CREDIT_TYPES['ScottishChildIsBetweenOneAndPrimarySchoolAge'],
            $evaluationFuture["credits"]
        );
        $this->assertNotEquals($evaluationNow->getEntitlement(), $evaluationFuture->getEntitlement());
    }

    /**
     * F10 (FIXED — regression test): ScottishFamilyHasNoEligibleChildren's
     * specification used to be OrSpec(AndSpec(IsBorn), NotSpec(IsBorn)) —
     * "born or not born" — satisfied by every child. It is now
     * OrSpec(AndSpec(IsBorn, IsScottishUnderSchoolAge), NotSpec(IsBorn)), so a
     * school-age child no longer qualifies the household.
     */
    public function testAuditF10ScottishEligibilitySpecExcludesSchoolAgeChildren(): void
    {
        $rule = new ScottishFamilyHasNoEligibleChildren();

        $property = new ReflectionProperty($rule, 'specification');
        $property->setAccessible(true);
        $specification = $property->getValue($rule);

        $baby = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::now()->startOfMonth()->subMonths(6)->toDateTimeString(),
        ]);
        $teenager = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::now()->startOfMonth()->subYears(13)->toDateTimeString(),
        ]);
        $unborn = factory(Child::class)->make([
            'born' => false,
            'dob' => Carbon::now()->startOfMonth()->addMonths(3)->toDateTimeString(),
        ]);

        // FIXED: babies and pregnancies satisfy the specification, but a
        // school-age teenager no longer does.
        $this->assertTrue($specification->isSatisfiedBy($baby));
        $this->assertFalse($specification->isSatisfiedBy($teenager));
        $this->assertTrue($specification->isSatisfiedBy($unborn));
    }

    /**
     * F16: BaseChildEvaluation::toReason() only includes 'value' when it is > 0,
     * so a Child credit configured with a negative value silently contributes
     * zero to the entitlement.
     */
    public function testAuditF16NegativeChildCreditValueIsDropped(): void
    {
        $this->markTestSkipped('AUDIT F16 — see docs/VOUCHER_EVALUATOR_AUDIT.md');
        $child = factory(Child::class)->states('underOne')->make();

        $rulesMods = collect([
            new Evaluation([
                "name" => "ChildIsUnderOne",
                "value" => -3,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
        ]);

        $evaluator = EvaluatorFactory::make($rulesMods);
        $evaluation = $evaluator->evaluate($child);

        $credits = $evaluation["credits"];
        $this->assertCount(1, $credits);

        // BUG: the credit fired, but its -3 value has been dropped entirely.
        $this->assertArrayNotHasKey('value', $credits[0]);
        $this->assertEquals(0, $evaluation->getEntitlement());
    }
}

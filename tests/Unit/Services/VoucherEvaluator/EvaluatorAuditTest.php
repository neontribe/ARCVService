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
     * F1 (FIXED — regression test): a disqualified child's reason is present in the raw 'disqualifiers'
     * bucket and now reaches getNoticeReasons(), because Valuation.php:47 merges
     * the 'disqualifiers' key instead of the non-existent 'disqualifications' key.
     */
    public function testAuditF1DisqualifierReasonsAreLostFromNoticeReasons(): void
    {
        // A six-year-old: disqualified by ChildIsPrimarySchoolAge, no notices due.
        $child = factory(Child::class)->make([
            'born' => true,
            'dob' => Carbon::now()->startOfMonth()->subYears(6)->toDateTimeString(),
        ]);

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($child);

        // The raw bucket has the reason...
        $this->assertContains(['reason' => 'Child|primary school age'], $evaluation["disqualifiers"]);

        // FIXED: ...and getNoticeReasons() includes it.
        $this->assertEquals([
            [
                'entity' => 'Child',
                'reason' => 'primary school age',
                'count' => 1,
            ],
        ], $evaluation->getNoticeReasons());
    }

    /**
     * F2 (FIXED — regression test): the 'almost 1' notice fires only when the
     * milestone is in the current or next month, rather than firing nearly two
     * months early due to Carbon 3 float truncation.
     */
    public function testAuditF2AlmostNoticeFiresAlmostTwoMonthsEarly(): void
    {
        // Child turns 1 two months in the future (e.g. November for a September offset).
        $offsetDate = Carbon::parse('2026-09-15');
        $dobTwoMonthsOut = Carbon::parse('2025-11-10');

        $childTwoMonthsOut = factory(Child::class)->make([
            'born' => true,
            'dob' => $dobTwoMonthsOut->toDateTimeString(),
        ]);

        $evaluator = EvaluatorFactory::make(null, $offsetDate);
        $evaluation = $evaluator->evaluate($childTwoMonthsOut);

        // FIXED: two months out, the notice does not fire.
        $this->assertNotContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $evaluation["notices"]);

        // Next month (October) or this month (November), the notice fires.
        $evaluationNextMonth = EvaluatorFactory::make(null, Carbon::parse('2026-10-15'))->evaluate($childTwoMonthsOut);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $evaluationNextMonth["notices"]);

        $evaluationSameMonth = EvaluatorFactory::make(null, Carbon::parse('2026-11-05'))->evaluate($childTwoMonthsOut);
        $this->assertContains(self::NOTICE_TYPES['ChildIsAlmostOne'], $evaluationSameMonth["notices"]);
    }

    /**
     * F3: an unborn child whose due date is months in the past still earns the
     * FamilyIsPregnant credit until manually marked as born or removed.
     * This is by design per client policy to avoid automatic cutoffs during
     * sensitive circumstances, relying on worker conversations instead.
     */
    public function testAuditF3PregnancyCreditSurvivesItsDueDate(): void
    {
        $family = factory(Family::class)->create();
        $overduePregnancy = factory(Child::class)->make([
            'born' => false,
            'dob' => Carbon::now()->startOfMonth()->subMonths(3)->toDateTimeString(),
        ]);
        $family->children()->save($overduePregnancy);

        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($family->fresh());

        // INTENDED: the due date is three months gone, but the credit persists until manual intervention.
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $evaluation["credits"]);
        // The unborn child earns the family pregnancy credit rather than born child credits.
        $this->assertEquals(4, $evaluation->getEntitlement());
    }

    /**
     * F3: a twin pregnancy (two unborn records) credits once per family,
     * which matches client policy (twins counted as a single pregnancy).
     */
    public function testAuditF3TwinPregnancyCreditsOnce(): void
    {
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

        // INTENDED: twins count as a single pregnancy credit per family.
        $this->assertCount(1, $pregnancyCredits);
        $this->assertEquals(4, $evaluation->getEntitlement());
    }

    /**
     * F4: an unconfirmed pregnancy (unborn child with verified = false) triggers
     * FamilyHasUnverifiedChildren, reminding staff to confirm the existence of
     * the child/pregnancy entity. Once confirmed (verified = true), the warning clears.
     */
    public function testAuditF4UnbornChildTriggersUnverifiedNotice(): void
    {
        $family = factory(Family::class)->create();
        $bornAndVerified = factory(Child::class)->states('betweenOneAndPrimarySchoolAge', 'verified')->make();
        $unbornUnverified = factory(Child::class)->states('unbornChild', 'unverified')->make();
        $family->children()->saveMany([$bornAndVerified, $unbornUnverified]);

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

        // INTENDED: An unconfirmed unborn child triggers the notice to remind staff to confirm existence.
        $this->assertContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $evaluation["notices"]);

        // Once confirmed (verified = true), the notice does not fire.
        $unbornUnverified->verified = true;
        $unbornUnverified->save();
        $evaluationCleared = $evaluator->evaluate($family->fresh());
        $this->assertNotContains(self::NOTICE_TYPES['FamilyHasUnverifiedChildren'], $evaluationCleared["notices"]);
    }

    /**
     * F5 (RESOLVED — regression test): HouseholdMember (a Child evaluation) used to
     * test leaving_on/rejoin_on on the Child, where they do not exist, so children
     * of a household that had left still earned their member credit. It now
     * evaluates the child's Family via Family::status(), matching HouseholdExists.
     */
    public function testAuditF5DepartedHouseholdStillCreditsMembers(): void
    {
        $family = factory(Family::class)->create();
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->save();

        $child = factory(Child::class)->states('betweenOneAndPrimarySchoolAge')->make();
        $family->children()->save($child);

        $evaluator = EvaluatorFactory::make($this->socialPrescribingMods());
        $evaluation = $evaluator->evaluate($family->fresh());

        $allCredits = $evaluation->flat("credits");

        // The family-level rule notices the departure...
        $this->assertNotContains(self::CREDIT_TYPES['HouseholdExists'], $allCredits);
        // FIXED: ...and so does the child-level member rule.
        $this->assertNotContains(self::CREDIT_TYPES['HouseholdMember'], $allCredits);
        // A departed household is entitled to nothing.
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

    /**
     * F5 (RESOLVED — regression test): a household that left and has since
     * rejoined is active again, so both the family and member credits return.
     */
    public function testAuditF5RejoinedHouseholdCreditsMembersAgain(): void
    {
        $family = factory(Family::class)->create();
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->rejoin_on = Carbon::now()->subMonths(1);
        $family->save();

        $child = factory(Child::class)->states('betweenOneAndPrimarySchoolAge')->make();
        $family->children()->save($child);

        $evaluator = EvaluatorFactory::make($this->socialPrescribingMods());
        $evaluation = $evaluator->evaluate($family->fresh());

        $allCredits = $evaluation->flat("credits");

        $this->assertContains(self::CREDIT_TYPES['HouseholdExists'], $allCredits);
        $this->assertContains(self::CREDIT_TYPES['HouseholdMember'], $allCredits);
        // HouseholdExists (7) + HouseholdMember (7) + DeductFromCarer (-7)
        $this->assertEquals(7, $evaluation->getEntitlement());
    }

    /**
     * F6 (RESOLVED — regression test): getEntitlement() clamps negative credit sums to 0,
     * ensuring entitlements cannot go negative even when negative credits outweigh positive ones.
     */
    public function testAuditF6EntitlementCanGoNegative(): void
    {
        $family = factory(Family::class)->create();
        $child = factory(Child::class)->create([
            'dob' => '2000-01-01',
            'family_id' => $family->id,
            'born' => 1,
        ]);
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->save();

        // With HouseholdMember disabled or zeroed, HouseholdExists failing, and DeductFromCarer firing:
        $mods = collect([
            new Evaluation([
                "name" => "HouseholdExists",
                "value" => 7,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "DeductFromCarer",
                "value" => -7,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
        ]);

        $evaluator = EvaluatorFactory::make($mods);
        $evaluation = $evaluator->evaluate($family->fresh());

        // FIXED: 0 — entitlement has a floor of 0.
        $this->assertEquals(0, $evaluation->getEntitlement());
    }

    /**
     * F7 (RESOLVED — regression test): DeductFromCarer uses isNotEmpty() on the
     * family's children collection rather than has('children'), ensuring the
     * deduction only fires when the family actually has child/carer records.
     */
    public function testAuditF7DeductFromCarerRequiresChildren(): void
    {
        $familyWithoutChildren = factory(Family::class)->create();

        $familyWithChild = factory(Family::class)->create();
        $child = factory(Child::class)->create([
            'dob' => '2000-01-01',
            'family_id' => $familyWithChild->id,
            'born' => 1,
        ]);

        $evaluator = EvaluatorFactory::make($this->socialPrescribingMods());

        $evalWithout = $evaluator->evaluate($familyWithoutChildren->fresh());
        $this->assertNotContains(self::CREDIT_TYPES['DeductFromCarer'], $evalWithout->flat('credits'));

        $evalWith = $evaluator->evaluate($familyWithChild->fresh());
        $this->assertContains(self::CREDIT_TYPES['DeductFromCarer'], $evalWith->flat('credits'));
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
     * F15 (RESOLVED — regression test): the pregnancy definition is consolidated.
     * FamilyIsPregnant evaluates $candidate->isPregnant() (matching NotSpec(new IsBorn())),
     * and FamilyHasNoEligibleChildren treats unborn children as qualifying satisfiers.
     * In both standard and social prescribing setups, pregnancy qualification behaves
     * consistently without conflicting accessor loops or date-parsing side effects.
     */
    public function testAuditF15PregnancyDefinitionConsolidated(): void
    {
        $pregnantFamily = factory(Family::class)->create();
        $unbornChild = factory(Child::class)->states('unbornChild')->make();
        $pregnantFamily->children()->save($unbornChild);

        // Standard evaluator: pregnancy qualifies and earns credit
        $evaluator = EvaluatorFactory::make();
        $evaluation = $evaluator->evaluate($pregnantFamily->fresh());

        $this->assertTrue($pregnantFamily->isPregnant());
        $this->assertContains(self::CREDIT_TYPES['FamilyIsPregnant'], $evaluation["credits"]);
        $this->assertEmpty($evaluation["disqualifiers"]);
        $this->assertEquals(4, $evaluation->getEntitlement());

        // Social prescribing evaluator (FamilyIsPregnant credit disabled):
        // Family is still qualifying/not disqualified, but earns 0 pregnancy credit.
        $spEvaluator = EvaluatorFactory::make($this->socialPrescribingMods());
        $spEvaluation = $spEvaluator->evaluate($pregnantFamily->fresh());

        $this->assertEmpty($spEvaluation["disqualifiers"]);
        $this->assertNotContains(self::CREDIT_TYPES['FamilyIsPregnant'], $spEvaluation["credits"]);
    }

    /**
     * F16 (RESOLVED — regression test): BaseChildEvaluation::toReason() includes
     * non-zero negative values, and test() short-circuits on null values.
     * Child credits configured with negative values preserve their 'value' key
     * and properly contribute to entitlement calculations.
     */
    public function testAuditF16NegativeChildCreditValueIsDropped(): void
    {
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

        // FIXED: the credit fired and its -3 value is preserved.
        $this->assertArrayHasKey('value', $credits[0]);
        $this->assertEquals(-3, $credits[0]['value']);
        $this->assertEquals(['reason' => 'Child|under 1 year old', 'value' => -3], $credits[0]);

        // When combined in a family with positive credits (e.g. pregnancy credit +4),
        // the negative child credit reduces entitlement: 4 + (-3) = 1.
        $family = factory(Family::class)->create();
        $unbornChild = factory(Child::class)->states('unbornChild')->make();
        $family->children()->save($unbornChild);
        $family->children()->save($child);
        $familyEvaluation = $evaluator->evaluate($family->fresh());

        $this->assertEquals(1, $familyEvaluation->getEntitlement());
    }
}

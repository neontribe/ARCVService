<?php

namespace Tests\Unit\Services\VoucherEvaluator;

use App\Centre;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Sponsor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyHasLeftProjectTest extends TestCase
{
    use RefreshDatabase;

    private function spMods()
    {
        $mods = [
            ["FamilyIsPregnant", null, "credits", "App\Family"],
            ["ChildIsBetweenOneAndPrimarySchoolAge", null, "credits", "App\Child"],
            ["ChildIsUnderOne", null, "credits", "App\Child"],
            ["ChildIsPrimarySchoolAge", null, "disqualifiers", "App\Child"],
            ["DeductFromCarer", -2, "credits", "App\Family"],
            ["HouseholdMember", 2, "credits", "App\Child"],
            ["HouseholdExists", 8, "credits", "App\Family"],
        ];
        return collect(array_map(function ($m) {
            return new Evaluation(["name" => $m[0], "value" => $m[1], "purpose" => $m[2], "entity" => $m[3]]);
        }, $mods));
    }

    private function makeFamily(?Carbon $leaving_on, ?Carbon $rejoin_on): Family
    {
        $family = factory(Family::class)->create([
            'leaving_on' => $leaving_on,
            'rejoin_on' => $rejoin_on,
        ]);
        // an under one (6 on family scheme) and a "carer" member for SP
        factory(Child::class)->state('underOne')->create(['family_id' => $family->id]);
        factory(Child::class)->create(['family_id' => $family->id, 'dob' => '2000-01-01', 'born' => 1]);
        return $family->fresh();
    }

    public static function statusProvider(): array
    {
        return [
            'never left' => [null, null, true],
            'left' => ['-10 days', null, false],
            'rejoined after leaving' => ['-10 days', '-5 days', true],
            'left again after rejoining' => ['-5 days', '-10 days', false],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testFamilySchemeLeaversGetNothing(?string $left, ?string $rejoined, bool $active): void
    {
        $family = $this->makeFamily(
            $left ? Carbon::now()->modify($left) : null,
            $rejoined ? Carbon::now()->modify($rejoined) : null
        );
        $valuation = EvaluatorFactory::make()->evaluate($family);

        $this->assertEquals($active, $valuation->getEligibility());
        $this->assertEquals($active ? 6 : 0, $valuation->getEntitlement());
        if (!$active) {
            $this->assertContains(
                ['reason' => 'Family|has left the project'],
                $valuation->disqualifiers
            );
        }
    }

    /**
     * @dataProvider statusProvider
     */
    public function testSocialPrescribingLeaversGetNothing(?string $left, ?string $rejoined, bool $active): void
    {
        $family = $this->makeFamily(
            $left ? Carbon::now()->modify($left) : null,
            $rejoined ? Carbon::now()->modify($rejoined) : null
        );
        $valuation = EvaluatorFactory::make($this->spMods())->evaluate($family);

        // 8 household + 2 x 2 members - 2 carer
        $this->assertEquals($active ? 10 : 0, $valuation->getEntitlement());
    }

    public function testRegistrationValuationOfALeaverIsZero(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $centre = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $family = $this->makeFamily(Carbon::now()->subDays(3), null);
        $registration = factory(Registration::class)->create([
            'centre_id' => $centre->id,
            'family_id' => $family->id,
        ]);

        $this->assertEquals(0, $registration->fresh()->getValuation()->getEntitlement());
        $this->assertEquals(0, $registration->fresh()->currentBundle()->entitlement);
    }
}

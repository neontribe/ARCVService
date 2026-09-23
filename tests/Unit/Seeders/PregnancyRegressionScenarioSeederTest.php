<?php

namespace Tests\Unit\Seeders;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Database\Seeders\PregnancyRegressionScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the manual-test fixtures in docs/tests/MANUAL_TEST_PREGNANCY_REGRESSION.md: the seeder must build
 * what the walkthrough says it builds, and its independently modelled be27ecd6 column must keep coming out
 * IDENTICAL to the live column on every row - the F15 refactor changed nothing a tester can see.
 */
class PregnancyRegressionScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PREGNANT = '+ 1x pregnant';
    private const BETWEEN_ONE_AND_SCHOOL = '+ 1x between 1 and start of primary school age';

    private const EXPECTED_ENTITLEMENTS = ['A' => 4, 'B' => 8, 'C' => 4, 'D' => 4, 'E' => 4];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedScenarios(string $today): PregnancyRegressionScenarioSeeder
    {
        Carbon::setTestNow(Carbon::parse($today));

        $seeder = new PregnancyRegressionScenarioSeeder();
        $seeder->run();
        return $seeder;
    }

    #[Test]
    public function it_builds_a_self_contained_sponsor_centre_and_login_with_default_rules()
    {
        $seeder = $this->seedScenarios('2026-09-23');

        $sponsor = Sponsor::where('shortcode', PregnancyRegressionScenarioSeeder::SPONSOR_SHORTCODE)->first();
        $this->assertNotNull($sponsor);
        $this->assertCount(0, $sponsor->evaluations);

        $centre = Centre::where('prefix', PregnancyRegressionScenarioSeeder::CENTRE_PREFIX)->first();
        $this->assertEquals($sponsor->id, $centre->sponsor_id);

        $user = CentreUser::where('email', PregnancyRegressionScenarioSeeder::USER_EMAIL)->first();
        $this->assertEquals($centre->id, $user->homeCentre?->getKey());

        $this->assertCount(count($seeder->scenarios()), Registration::where('centre_id', $centre->id)->get());
        $this->assertCount(5, $seeder->rows);
        foreach ($seeder->rows as $row) {
            $this->assertStringStartsWith(PregnancyRegressionScenarioSeeder::CENTRE_PREFIX, $row['rvid']);
            $this->assertStringStartsWith(PregnancyRegressionScenarioSeeder::CARER_PREFIX . '-' . $row['key'], $row['carer']);
        }
    }

    #[Test]
    public function it_is_idempotent()
    {
        $this->seedScenarios('2026-09-23');
        $this->seedScenarios('2026-09-23');

        $this->assertCount(1, Sponsor::withTrashed()->where('shortcode', 'PREG')->get());
        $this->assertCount(1, Centre::withTrashed()->where('prefix', 'PREG')->get());
        $this->assertCount(1, CentreUser::withTrashed()->where('email', PregnancyRegressionScenarioSeeder::USER_EMAIL)->get());
        $centre = Centre::where('prefix', 'PREG')->first();
        $this->assertCount(5, Registration::where('centre_id', $centre->id)->get());
    }

    /**
     * The whole point of the kit: the live column and the modelled be27ecd6 column agree on every family.
     */
    #[Test]
    public function every_row_is_identical_on_both_branches()
    {
        $rows = $this->seedScenarios('2026-09-23')->rows;

        foreach (self::EXPECTED_ENTITLEMENTS as $key => $entitlement) {
            $row = $rows[$key];
            $this->assertSame($entitlement, $row['entitlement'], "entitlement for $key");
            $this->assertSame($entitlement, $row['unpatched_entitlement'], "unpatched entitlement for $key");
            $this->assertStringStartsWith($entitlement . '/wk', $row['patched']);
            $this->assertStringStartsWith($entitlement . '/wk', $row['unpatched']);

            $this->assertSame($row['patched'], $row['unpatched'], "$key columns should match");
            $this->assertSame($row['is_pregnant'], $row['unpatched_is_pregnant'], "$key pregnancy predicate");
            $this->assertSame('', $row['differs'], "$key must not differ");
            $this->assertSame([], $row['notices'], "$key should show no notices");
        }

        // A-D carry exactly one pregnancy credit on both sides; E none.
        foreach (['A', 'B', 'C', 'D'] as $key) {
            $this->assertTrue($rows[$key]['is_pregnant'], "$key is pregnant");
            $this->assertStringContainsString(self::PREGNANT, $rows[$key]['patched'], "$key patched");
            $this->assertStringContainsString(self::PREGNANT, $rows[$key]['unpatched'], "$key unpatched");
            $this->assertSame(1, substr_count($rows[$key]['patched'], 'pregnant'), "$key has a single pregnancy line");
        }
        $this->assertFalse($rows['E']['is_pregnant']);
        $this->assertStringNotContainsString('pregnant', $rows['E']['patched']);
        $this->assertStringNotContainsString('pregnant', $rows['E']['unpatched']);

        // B and E carry the toddler credit; the pregnancy-only families do not.
        foreach (['B', 'E'] as $key) {
            $this->assertStringContainsString(self::BETWEEN_ONE_AND_SCHOOL, $rows[$key]['patched'], "$key patched");
        }
        foreach (['A', 'C', 'D'] as $key) {
            $this->assertStringNotContainsString('between', $rows[$key]['patched'], "$key patched");
        }
    }

    /**
     * The one real change since be27ecd6 - Family::expecting picks the earliest, not the last, unborn child -
     * shows up in the internal due date on C and nowhere else.
     */
    #[Test]
    public function only_the_internal_due_date_on_c_differs()
    {
        $rows = $this->seedScenarios('2026-09-23')->rows;

        $this->assertSame('2026-11-01', $rows['C']['expecting_new']);
        $this->assertSame('2027-02-01', $rows['C']['expecting_old']);
        $this->assertNotSame($rows['C']['expecting_new'], $rows['C']['expecting_old']);
        $this->assertSame('', $rows['C']['differs']);

        foreach (['A' => '2026-12-01', 'B' => '2026-12-01', 'D' => '2026-07-01'] as $key => $due) {
            $this->assertSame($due, $rows[$key]['expecting_new'], "$key new due date");
            $this->assertSame($due, $rows[$key]['expecting_old'], "$key old due date");
        }

        $this->assertNull($rows['E']['expecting_new']);
        $this->assertNull($rows['E']['expecting_old']);
    }

    /**
     * No school-month arithmetic is involved, so the kit reads the same whenever it is seeded.
     */
    #[Test]
    public function it_is_date_independent()
    {
        $rows = $this->seedScenarios('2026-02-23')->rows;

        foreach (self::EXPECTED_ENTITLEMENTS as $key => $entitlement) {
            $this->assertSame($entitlement, $rows[$key]['entitlement'], "entitlement for $key");
            $this->assertSame($rows[$key]['patched'], $rows[$key]['unpatched'], "$key columns should match");
            $this->assertSame('', $rows[$key]['differs'], "$key must not differ");
            $this->assertSame([], $rows[$key]['notices'], "$key should show no notices");
        }

        $this->assertSame('2026-04-01', $rows['C']['expecting_new']);
        $this->assertSame('2026-07-01', $rows['C']['expecting_old']);
        $this->assertSame('2025-12-01', $rows['D']['expecting_new']);
    }
}

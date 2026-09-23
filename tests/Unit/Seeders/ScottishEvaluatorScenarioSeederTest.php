<?php

namespace Tests\Unit\Seeders;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Database\Seeders\ScottishEvaluatorScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the manual-test fixtures in docs/tests/MANUAL_TEST_SCOTTISH_EVALUATOR.md: the seeder must build
 * what the walkthrough says it builds, and its model of the unpatched (ae14917c) behaviour must keep
 * producing the "broken" numbers the tester is told to look for.
 */
class ScottishEvaluatorScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedScenarios(string $today, int $schoolMonth): ScottishEvaluatorScenarioSeeder
    {
        Carbon::setTestNow(Carbon::parse($today));
        Config::set('arc.scottish_school_month', $schoolMonth);

        $seeder = new ScottishEvaluatorScenarioSeeder();
        $seeder->run();
        return $seeder;
    }

    #[Test]
    public function it_builds_a_self_contained_sponsor_centre_and_login()
    {
        $seeder = $this->seedScenarios('2026-09-23', 8);

        $sponsor = Sponsor::where('shortcode', ScottishEvaluatorScenarioSeeder::SPONSOR_SHORTCODE)->first();
        $this->assertNotNull($sponsor);
        $this->assertTrue($sponsor->evaluations->contains('name', 'ScottishChildCanDefer'));
        $this->assertFalse($sponsor->evaluations->contains('name', 'FamilyHasUnverifiedChildren'));

        $centre = Centre::where('prefix', ScottishEvaluatorScenarioSeeder::CENTRE_PREFIX)->first();
        $this->assertEquals($sponsor->id, $centre->sponsor_id);

        $user = CentreUser::where('email', ScottishEvaluatorScenarioSeeder::USER_EMAIL)->first();
        $this->assertEquals($centre->id, $user->homeCentre?->getKey());

        $this->assertCount(count($seeder->scenarios()), Registration::where('centre_id', $centre->id)->get());
        foreach ($seeder->rows as $row) {
            $this->assertStringStartsWith(ScottishEvaluatorScenarioSeeder::CENTRE_PREFIX, $row['rvid']);
        }
    }

    #[Test]
    public function it_is_idempotent()
    {
        $this->seedScenarios('2026-09-23', 8);
        $this->seedScenarios('2026-09-23', 8);

        $this->assertCount(1, Sponsor::withTrashed()->where('shortcode', 'SCOT')->get());
        $this->assertCount(1, Centre::withTrashed()->where('prefix', 'SCOT')->get());
        $this->assertCount(1, CentreUser::withTrashed()->where('email', ScottishEvaluatorScenarioSeeder::USER_EMAIL)->get());
        $centre = Centre::where('prefix', 'SCOT')->first();
        $this->assertCount(11, Registration::where('centre_id', $centre->id)->get());
    }

    /**
     * September, production school month: the entitlement-affecting fixes (F9/F10 and F11) are visible.
     */
    #[Test]
    public function it_shows_the_entitlement_fixes_in_september_with_the_default_school_month()
    {
        $rows = $this->seedScenarios('2026-09-23', 8)->rows;

        // [patched (live), unpatched (modelled)]
        $expected = [
            'A' => [4, 0],   // F9/F10 under-credit
            'B' => [4, 0],   // F11 deferral ignored at 5
            'C' => [4, 4],
            'D' => [8, 8],
            'E' => [0, 0],   // needs env to differ
            'F' => [0, 0],
            'G' => [4, 4],
            'H1' => [4, 0],  // 4y3m only child: old Sep-Dec bug again
            'H2' => [0, 0],
            'H3' => [0, 0],
            'H4' => [4, 4],
        ];

        foreach ($expected as $key => [$patched, $unpatched]) {
            $this->assertSame($patched, $rows[$key]['entitlement'], "patched entitlement for $key");
            $this->assertSame($unpatched, $rows[$key]['unpatched_entitlement'], "unpatched entitlement for $key");
        }

        $this->assertSame('YES', $rows['A']['differs']);
        $this->assertSame('YES', $rows['B']['differs']);
        $this->assertSame('', $rows['D']['differs']);
        $this->assertStringContainsString('1x primary school age (SCOTLAND)', $rows['D']['patched']);
        $this->assertStringContainsString('1x between 1 and start of primary school age (SCOTLAND)', $rows['D']['patched']);

        // No "almost"/"defer" notices from either branch in September with an August start. The only
        // Reminder line on this branch is the family disqualifier reason, which reaches the UI since the
        // F1 fix (later on this branch than ae14917c, where the unpatched column stays silent).
        $disqualified = '! 1x has no child under primary school age then children of primary school age get (SCOTLAND)';
        foreach ($rows as $key => $row) {
            $this->assertStringNotContainsString('!', $row['unpatched'], "$key unpatched");
            if ($row['entitlement'] === 0) {
                $this->assertStringContainsString($disqualified, $row['patched'], "$key patched");
                $this->assertSame(1, substr_count($row['patched'], '!'), "$key patched has only the disqualifier");
            } else {
                $this->assertStringNotContainsString('!', $row['patched'], "$key patched");
            }
        }
        $this->assertSame(['E', 'F', 'H2', 'H3'], array_keys(array_filter($rows, fn($row) => $row['entitlement'] === 0)));
    }

    /**
     * Setting the school month to the current month reproduces the Jan-Jul over-credit direction of F9.
     */
    #[Test]
    public function it_shows_the_over_credit_direction_with_school_month_set_to_the_current_month()
    {
        $rows = $this->seedScenarios('2026-09-23', 9)->rows;

        $this->assertSame(0, $rows['E']['entitlement']);
        $this->assertSame(4, $rows['E']['unpatched_entitlement']);
        $this->assertSame('YES', $rows['E']['differs']);
    }

    /**
     * Setting the school month to next month opens both notice windows, for different children.
     */
    #[Test]
    public function it_shows_the_notice_fixes_with_school_month_set_to_next_month()
    {
        $rows = $this->seedScenarios('2026-09-23', 10)->rows;

        $almost = '! 1x almost primary school age (SCOTLAND)';
        $defer = '! 1x able to defer (SCOTLAND)';

        // H1: old fires on the age string alone; fix knows school is a year away.
        $this->assertStringNotContainsString($almost, $rows['H1']['patched']);
        $this->assertStringContainsString($almost, $rows['H1']['unpatched']);
        $this->assertStringContainsString($defer, $rows['H1']['unpatched']);

        // H2: fix uses the real cohort; old string check misses a 5y5m child.
        $this->assertStringContainsString($almost, $rows['H2']['patched']);
        $this->assertStringNotContainsString($defer, $rows['H2']['patched']);
        $this->assertStringNotContainsString($almost, $rows['H2']['unpatched']);

        // H3: both almost, only the fix allows deferral.
        $this->assertStringContainsString($almost, $rows['H3']['patched']);
        $this->assertStringContainsString($defer, $rows['H3']['patched']);
        $this->assertStringContainsString($almost, $rows['H3']['unpatched']);
        $this->assertStringNotContainsString($defer, $rows['H3']['unpatched']);

        // H4: already deferred - fix is silent, old still nags.
        $this->assertStringNotContainsString('!', $rows['H4']['patched']);
        $this->assertStringContainsString($almost, $rows['H4']['unpatched']);

        foreach (['H1', 'H2', 'H3', 'H4'] as $key) {
            $this->assertSame('YES', $rows[$key]['differs'], "$key should differ");
        }
    }

    /**
     * In January the Sep-Dec under-credit disappears but F11 is still visible.
     */
    #[Test]
    public function it_keeps_f11_visible_in_january()
    {
        $rows = $this->seedScenarios('2027-01-15', 8)->rows;

        $this->assertSame(4, $rows['A']['entitlement']);
        $this->assertSame(4, $rows['A']['unpatched_entitlement']);
        $this->assertSame('', $rows['A']['differs']);

        $this->assertSame(4, $rows['B']['entitlement']);
        $this->assertSame(0, $rows['B']['unpatched_entitlement']);
        $this->assertSame('YES', $rows['B']['differs']);
    }
}

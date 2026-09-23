<?php

namespace Tests\Unit\Seeders;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Database\Seeders\EvaluatorNoticeScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the manual-test fixtures in docs/tests/MANUAL_TEST_EVALUATOR_NOTICES.md: the seeder must build
 * what the walkthrough says it builds, and its model of the unpatched (1f019ce7) notices must keep
 * producing the "broken" Reminder boxes the tester is told to look for.
 */
class EvaluatorNoticeScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    private const ALMOST_ONE = '! 1x almost 1 year old';
    private const ALMOST_SCHOOL = '! 1x almost primary school age';
    private const AT_SCHOOL = '! 1x primary school age';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedScenarios(string $today, int $schoolMonth): EvaluatorNoticeScenarioSeeder
    {
        Carbon::setTestNow(Carbon::parse($today));
        Config::set('arc.school_month', $schoolMonth);

        $seeder = new EvaluatorNoticeScenarioSeeder();
        $seeder->run();
        return $seeder;
    }

    #[Test]
    public function it_builds_a_self_contained_sponsor_centre_and_login_with_default_rules()
    {
        $seeder = $this->seedScenarios('2026-09-23', 9);

        $sponsor = Sponsor::where('shortcode', EvaluatorNoticeScenarioSeeder::SPONSOR_SHORTCODE)->first();
        $this->assertNotNull($sponsor);
        $this->assertCount(0, $sponsor->evaluations);

        $centre = Centre::where('prefix', EvaluatorNoticeScenarioSeeder::CENTRE_PREFIX)->first();
        $this->assertEquals($sponsor->id, $centre->sponsor_id);

        $user = CentreUser::where('email', EvaluatorNoticeScenarioSeeder::USER_EMAIL)->first();
        $this->assertEquals($centre->id, $user->homeCentre?->getKey());

        $this->assertCount(count($seeder->scenarios()), Registration::where('centre_id', $centre->id)->get());
        $this->assertCount(5, $seeder->rows);
        foreach ($seeder->rows as $row) {
            $this->assertStringStartsWith(EvaluatorNoticeScenarioSeeder::CENTRE_PREFIX, $row['rvid']);
            $this->assertStringStartsWith(EvaluatorNoticeScenarioSeeder::CARER_PREFIX . '-' . $row['key'], $row['carer']);
        }
    }

    #[Test]
    public function it_is_idempotent()
    {
        $this->seedScenarios('2026-09-23', 9);
        $this->seedScenarios('2026-09-23', 9);

        $this->assertCount(1, Sponsor::withTrashed()->where('shortcode', 'NOTE')->get());
        $this->assertCount(1, Centre::withTrashed()->where('prefix', 'NOTE')->get());
        $this->assertCount(1, CentreUser::withTrashed()->where('email', EvaluatorNoticeScenarioSeeder::USER_EMAIL)->get());
        $centre = Centre::where('prefix', 'NOTE')->first();
        $this->assertCount(5, Registration::where('centre_id', $centre->id)->get());
    }

    /**
     * September, production school month: F1 on A/B/C, the start-month direction of F2 on C, controls unchanged.
     */
    #[Test]
    public function it_shows_the_notice_fixes_in_september_with_the_default_school_month()
    {
        $rows = $this->seedScenarios('2026-09-23', 9)->rows;

        // Entitlement never moves between branches; both columns are built from the same live number.
        foreach (['A' => 4, 'B' => 0, 'C' => 6, 'D' => 6, 'E' => 8] as $key => $entitlement) {
            $this->assertSame($entitlement, $rows[$key]['entitlement'], "entitlement for $key");
            $this->assertStringStartsWith($entitlement . '/wk', $rows[$key]['patched']);
            $this->assertStringStartsWith($entitlement . '/wk', $rows[$key]['unpatched']);
        }

        // A and B: F1 - disqualifier reason reaches the Reminder box only on this branch.
        foreach (['A', 'B'] as $key) {
            $this->assertStringContainsString(self::AT_SCHOOL, $rows[$key]['patched'], "$key patched");
            $this->assertStringNotContainsString('!', $rows[$key]['unpatched'], "$key unpatched");
            $this->assertSame('YES', $rows[$key]['differs'], "$key should differ");
        }

        // C: fix fires "almost" during the start month and surfaces the disqualifier; old code shows nothing.
        $this->assertStringContainsString(self::ALMOST_SCHOOL, $rows['C']['patched']);
        $this->assertStringContainsString(self::AT_SCHOOL, $rows['C']['patched']);
        $this->assertStringNotContainsString(self::ALMOST_ONE, $rows['C']['patched']);
        $this->assertSame([], $rows['C']['unpatched_notices']);
        $this->assertSame('YES', $rows['C']['differs']);

        // D: almost-one control - identical on both branches for a 1st-of-month DOB.
        $this->assertStringContainsString(self::ALMOST_ONE, $rows['D']['patched']);
        $this->assertStringContainsString(self::ALMOST_ONE, $rows['D']['unpatched']);
        $this->assertSame('', $rows['D']['differs']);

        // E: ordinary family, no notices on either side.
        $this->assertSame([], $rows['E']['patched_notices']);
        $this->assertSame([], $rows['E']['unpatched_notices']);
        $this->assertSame('', $rows['E']['differs']);
    }

    /**
     * ARC_SCHOOL_MONTH = this month + 2: the old code shows "almost primary school age" two months early.
     */
    #[Test]
    public function it_shows_the_two_months_early_direction_with_school_month_two_ahead()
    {
        $rows = $this->seedScenarios('2026-09-23', 11)->rows;

        $this->assertSame(10, $rows['C']['entitlement']);
        $this->assertStringStartsWith('10/wk', $rows['C']['unpatched']);
        $this->assertStringNotContainsString(self::ALMOST_SCHOOL, $rows['C']['patched']);
        $this->assertStringContainsString(self::ALMOST_SCHOOL, $rows['C']['unpatched']);
        $this->assertSame('YES', $rows['C']['differs']);
    }

    /**
     * ARC_SCHOOL_MONTH = this month + 1: the control where both branches show the notice.
     */
    #[Test]
    public function it_shows_the_notice_on_both_branches_with_school_month_one_ahead()
    {
        $rows = $this->seedScenarios('2026-09-23', 10)->rows;

        $this->assertSame(10, $rows['C']['entitlement']);
        $this->assertStringContainsString(self::ALMOST_SCHOOL, $rows['C']['patched']);
        $this->assertStringContainsString(self::ALMOST_SCHOOL, $rows['C']['unpatched']);
        $this->assertSame('', $rows['C']['differs']);
    }

    /**
     * July with the production month: the classic F2 symptom from the audit, a notice two months before start.
     */
    #[Test]
    public function it_shows_the_july_false_positive_with_the_default_school_month()
    {
        $rows = $this->seedScenarios('2026-07-23', 9)->rows;

        $this->assertSame(10, $rows['C']['entitlement']);
        $this->assertStringNotContainsString(self::ALMOST_SCHOOL, $rows['C']['patched']);
        $this->assertStringContainsString(self::ALMOST_SCHOOL, $rows['C']['unpatched']);
        $this->assertSame('YES', $rows['C']['differs']);

        // F1 rows are month-independent.
        $this->assertStringContainsString(self::AT_SCHOOL, $rows['B']['patched']);
        $this->assertStringNotContainsString('!', $rows['B']['unpatched']);
    }
}

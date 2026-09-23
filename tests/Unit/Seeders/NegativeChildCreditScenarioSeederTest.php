<?php

namespace Tests\Unit\Seeders;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Database\Seeders\NegativeChildCreditScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the manual-test fixtures in docs/tests/MANUAL_TEST_NEGATIVE_CHILD_CREDIT.md: the seeder must build
 * what the walkthrough says it builds, and its model of the unpatched (48331e9f) behaviour must keep
 * producing the "broken" numbers the tester is told to look for.
 */
class NegativeChildCreditScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedScenarios(string $today, int $schoolMonth = 9): NegativeChildCreditScenarioSeeder
    {
        Carbon::setTestNow(Carbon::parse($today));
        Config::set('arc.school_month', $schoolMonth);

        $seeder = new NegativeChildCreditScenarioSeeder();
        $seeder->run();
        return $seeder;
    }

    #[Test]
    public function it_builds_a_self_contained_sponsor_centre_and_login()
    {
        $seeder = $this->seedScenarios('2026-09-23');

        $sponsor = Sponsor::where('shortcode', NegativeChildCreditScenarioSeeder::SPONSOR_SHORTCODE)->first();
        $this->assertNotNull($sponsor);
        $this->assertCount(2, $sponsor->evaluations);
        $this->assertSame(
            [
                ['name' => 'ChildIsPrimarySchoolAge', 'purpose' => 'credits', 'value' => NegativeChildCreditScenarioSeeder::SCHOOL_AGE_DEDUCTION],
                ['name' => 'ChildIsPrimarySchoolAge', 'purpose' => 'disqualifiers', 'value' => null],
            ],
            $sponsor->evaluations
                ->map(fn($evaluation) => [
                    'name' => $evaluation->getAttribute('name'),
                    'purpose' => $evaluation->getAttribute('purpose'),
                    'value' => $evaluation->getAttribute('value') === null ? null : (int) $evaluation->getAttribute('value'),
                ])
                ->sortBy('purpose')
                ->values()
                ->all()
        );

        $centre = Centre::where('prefix', NegativeChildCreditScenarioSeeder::CENTRE_PREFIX)->first();
        $this->assertEquals($sponsor->id, $centre->sponsor_id);

        $user = CentreUser::where('email', NegativeChildCreditScenarioSeeder::USER_EMAIL)->first();
        $this->assertEquals($centre->id, $user->homeCentre?->getKey());

        $this->assertCount(count($seeder->scenarios()), Registration::where('centre_id', $centre->id)->get());
        foreach ($seeder->rows as $row) {
            $this->assertStringStartsWith(NegativeChildCreditScenarioSeeder::CENTRE_PREFIX, $row['rvid']);
        }
    }

    #[Test]
    public function it_is_idempotent()
    {
        $this->seedScenarios('2026-09-23');
        $this->seedScenarios('2026-09-23');

        $this->assertCount(1, Sponsor::withTrashed()->where('shortcode', 'NEGV')->get());
        $this->assertCount(1, Centre::withTrashed()->where('prefix', 'NEGV')->get());
        $this->assertCount(1, CentreUser::withTrashed()->where('email', NegativeChildCreditScenarioSeeder::USER_EMAIL)->get());
        $centre = Centre::where('prefix', 'NEGV')->first();
        $this->assertCount(6, Registration::where('centre_id', $centre->id)->get());
    }

    /**
     * The headline: negative Child credits now reduce the total; the unpatched model counts them as 0.
     */
    #[Test]
    public function it_shows_the_negative_child_credit_reducing_the_entitlement()
    {
        $rows = $this->seedScenarios('2026-09-23')->rows;

        // [patched (live), unpatched (modelled)]
        $expected = [
            'A' => [6, 6],
            'B' => [2, 4],
            'C' => [6, 8],
            'D' => [0, 4],
            'E' => [0, 0],
            'F' => [10, 10],
        ];

        foreach ($expected as $key => [$patched, $unpatched]) {
            $this->assertSame($patched, $rows[$key]['entitlement'], "patched entitlement for $key");
            $this->assertSame($unpatched, $rows[$key]['unpatched_entitlement'], "unpatched entitlement for $key");
        }

        foreach (['B', 'C', 'D', 'E'] as $key) {
            $this->assertSame('YES', $rows[$key]['differs'], "$key should differ");
        }
        foreach (['A', 'F'] as $key) {
            $this->assertSame('', $rows[$key]['differs'], "$key is a control");
        }

        // The deduction is visible as a credit line on both sides; only its voucher figure changes.
        $this->assertStringContainsString('+ 1x primary school age (-2)', $rows['B']['patched']);
        $this->assertStringContainsString('+ 1x primary school age (0)', $rows['B']['unpatched']);
        $this->assertStringContainsString('+ 1x between 1 and start of primary school age (4)', $rows['B']['patched']);
        $this->assertStringContainsString('+ 1x between 1 and start of primary school age (4)', $rows['B']['unpatched']);

        $this->assertStringContainsString('+ 2x primary school age (-4)', $rows['D']['patched']);
        $this->assertStringContainsString('+ 2x primary school age (0)', $rows['D']['unpatched']);

        // E: clamped at zero on both sides, wording is the only difference.
        $this->assertStringStartsWith("0/wk\n+ 1x primary school age (-2)", $rows['E']['patched']);
        $this->assertStringStartsWith("0/wk\n+ 1x primary school age (0)", $rows['E']['unpatched']);

        $this->assertStringContainsString('+ 1x pregnant (4)', $rows['F']['patched']);
        $this->assertStringContainsString('+ 1x under 1 year old (6)', $rows['F']['patched']);

        // No notices or disqualifier reminders anywhere: the ages avoid every "almost" window and the
        // school-age disqualifier is switched off for this sponsor.
        foreach ($rows as $key => $row) {
            $this->assertStringNotContainsString('!', $row['patched'], "$key patched");
            $this->assertStringNotContainsString('!', $row['unpatched'], "$key unpatched");
        }
    }

    /**
     * Ages are chosen clear of the school-start boundary, so the figures do not move with the calendar.
     */
    #[Test]
    public function it_reads_the_same_in_other_months()
    {
        foreach (['2027-01-15', '2027-06-01', '2027-08-31', '2026-12-01'] as $today) {
            $rows = $this->seedScenarios($today)->rows;

            $this->assertSame(2, $rows['B']['entitlement'], "B patched on $today");
            $this->assertSame(4, $rows['B']['unpatched_entitlement'], "B unpatched on $today");
            $this->assertSame(0, $rows['D']['entitlement'], "D patched on $today");
            $this->assertSame(4, $rows['D']['unpatched_entitlement'], "D unpatched on $today");
            $this->assertSame('', $rows['A']['differs'], "A on $today");
            $this->assertSame('', $rows['F']['differs'], "F on $today");
        }
    }
}

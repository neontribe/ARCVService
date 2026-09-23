<?php

namespace Tests\Unit\Seeders;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use Database\Seeders\SocialPrescribingScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the manual-test fixtures in docs/tests/MANUAL_TEST_SP_EVALUATOR.md: the seeder must build what the
 * walkthrough says it builds, and its arithmetic model of the unpatched (b0abd058) social-prescribing rules
 * must keep producing the "broken" totals the tester is told to look for.
 */
class SocialPrescribingScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    private const HOUSEHOLD_EXISTS = '1x Family|exists (+10)';
    private const CARER_DEDUCTION = '1x Family| (-7)';
    private const MEMBER = 'Child|member of the household';

    /** Expected weekly totals, keyed by scenario: [this branch, b0abd058]. */
    private const EXPECTED = [
        'A' => [10, 3],
        'B' => [10, 10],
        'C' => [17, 17],
        'D' => [0, -7],
        'E' => [0, 0],
        'F' => [0, 7],
        'G' => [17, 17],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-06-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedScenarios(): SocialPrescribingScenarioSeeder
    {
        $seeder = new SocialPrescribingScenarioSeeder();
        $seeder->run();
        return $seeder;
    }

    #[Test]
    public function it_seeds_a_self_contained_sp_sponsor_centre_and_downloader_login()
    {
        $this->seedScenarios();

        $sponsor = Sponsor::where('shortcode', SocialPrescribingScenarioSeeder::SPONSOR_SHORTCODE)->first();
        $this->assertNotNull($sponsor);
        $this->assertSame(1, (int) $sponsor->programme);
        $this->assertCount(9, $sponsor->evaluations);

        foreach (['HouseholdExists' => 10, 'HouseholdMember' => 7, 'DeductFromCarer' => -7] as $name => $value) {
            /** @var Evaluation $rule */
            $rule = Evaluation::where('sponsor_id', $sponsor->id)->where('name', $name)->firstOrFail();
            $this->assertSame($value, (int) $rule->value, "value for $name");
            $this->assertSame('credits', $rule->purpose, "purpose for $name");
        }

        $centre = Centre::where('prefix', SocialPrescribingScenarioSeeder::CENTRE_PREFIX)->first();
        $this->assertNotNull($centre);
        $this->assertEquals($sponsor->id, $centre->sponsor_id);
        $this->assertSame('individual', $centre->print_pref);

        $user = CentreUser::where('email', SocialPrescribingScenarioSeeder::USER_EMAIL)->first();
        $this->assertNotNull($user);
        $this->assertSame('centre_user', $user->role);
        $this->assertTrue($user->downloader);
        $this->assertEquals($centre->id, $user->homeCentre?->getKey());
    }

    #[Test]
    public function it_seeds_one_registration_per_scenario()
    {
        $seeder = $this->seedScenarios();
        $centre = Centre::where('prefix', SocialPrescribingScenarioSeeder::CENTRE_PREFIX)->first();

        $this->assertCount(7, $seeder->scenarios());
        $this->assertCount(7, Registration::where('centre_id', $centre->id)->get());
        $this->assertCount(7, $seeder->rows);
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'F', 'G'], array_keys($seeder->rows));

        $expectedMembers = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 0, 'E' => 1, 'F' => 2, 'G' => 2];
        $expectedStatus = [
            'A' => 'active', 'B' => 'active', 'C' => 'active',
            'D' => 'left', 'E' => 'left', 'F' => 'left',
            'G' => 'rejoined',
        ];

        foreach ($seeder->rows as $key => $row) {
            $this->assertStringStartsWith(SocialPrescribingScenarioSeeder::CENTRE_PREFIX, $row['rvid']);
            $this->assertStringStartsWith(SocialPrescribingScenarioSeeder::CARER_PREFIX . '-' . $key . ' ', $row['carer']);
            $this->assertSame($expectedMembers[$key], $row['members'], "members for $key");
            $this->assertSame($expectedStatus[$key], $row['status'], "status for $key");

            $registration = Registration::withFullFamily()->find($row['registration_id']);
            $this->assertCount($expectedMembers[$key], $registration->family->children, "child rows for $key");
            $this->assertSame(
                $expectedStatus[$key] !== 'left',
                $registration->family->status(),
                "Family::status() for $key"
            );
        }

        // Departed households carry a leaving date; the rejoined one also has a later rejoin date.
        $left = Registration::find($seeder->rows['D']['registration_id'])->family;
        $this->assertNotNull($left->leaving_on);
        $this->assertNull($left->rejoin_on);
        $rejoined = Registration::find($seeder->rows['G']['registration_id'])->family;
        $this->assertTrue(Carbon::parse($rejoined->rejoin_on)->gt(Carbon::parse($rejoined->leaving_on)));
    }

    #[Test]
    public function it_is_idempotent_and_never_deletes_vouchers()
    {
        $first = $this->seedScenarios();

        // A tester opened the voucher-manager and allocated a voucher to family C.
        $bundle = factory(Bundle::class)->create(['registration_id' => $first->rows['C']['registration_id']]);
        $voucher = factory(Voucher::class)->create(['bundle_id' => $bundle->id]);

        $this->seedScenarios();

        $this->assertCount(1, Sponsor::withTrashed()->where('shortcode', 'SPHH')->get());
        $this->assertCount(1, Centre::withTrashed()->where('prefix', 'SPHH')->get());
        $this->assertCount(1, CentreUser::withTrashed()->where('email', SocialPrescribingScenarioSeeder::USER_EMAIL)->get());

        $sponsor = Sponsor::where('shortcode', 'SPHH')->first();
        $this->assertCount(9, Evaluation::where('sponsor_id', $sponsor->id)->get());

        $centre = Centre::where('prefix', 'SPHH')->first();
        $registrations = Registration::where('centre_id', $centre->id)->get();
        $this->assertCount(7, $registrations);
        $this->assertCount(7, Family::whereIn('id', $registrations->pluck('family_id'))->get());
        $this->assertCount(7, Carer::where('name', 'like', 'SPHH-%')->get());
        // 0+1+2+0+1+2+2 member records, none left over from the first run.
        $this->assertCount(8, Child::whereIn('family_id', $registrations->pluck('family_id'))->get());
        // No orphaned children or carers from the first run (the factory bundle above made no family of its own).
        $this->assertSame(0, Child::whereNotIn('family_id', Family::pluck('id'))->count());
        $this->assertSame(0, Carer::whereNotIn('family_id', Family::pluck('id'))->count());

        $this->assertNull(Bundle::find($bundle->id));
        $voucher->refresh();
        $this->assertNull($voucher->bundle_id);
    }

    #[Test]
    public function the_cheat_sheet_matches_the_audit_numbers()
    {
        $rows = $this->seedScenarios()->rows;

        foreach (self::EXPECTED as $key => [$patched, $unpatched]) {
            $this->assertSame($patched, $rows[$key]['entitlement'], "this-branch entitlement for $key");
            $this->assertSame($unpatched, $rows[$key]['unpatched_entitlement'], "b0abd058 entitlement for $key");
            $this->assertStringStartsWith($patched . '/wk', $rows[$key]['patched']);
            $this->assertStringStartsWith($unpatched . '/wk', $rows[$key]['unpatched']);
            $this->assertSame($patched === $unpatched ? '' : 'YES', $rows[$key]['differs'], "differs for $key");
        }

        $this->assertSame(['A', 'D', 'F'], array_keys(array_filter($rows, fn($row) => $row['differs'] === 'YES')));

        // F6: this branch never goes below zero, the old code did.
        $this->assertLessThan(0, $rows['D']['unpatched_entitlement']);
        foreach ($rows as $row) {
            $this->assertGreaterThanOrEqual(0, $row['entitlement']);
        }
    }

    #[Test]
    public function the_unpatched_column_follows_the_old_rules_only()
    {
        $rows = $this->seedScenarios()->rows;

        // F7 (A): old code deducted for the carer even with no member records; new code does not.
        $this->assertContains(self::HOUSEHOLD_EXISTS, $rows['A']['unpatched_credits']);
        $this->assertContains(self::CARER_DEDUCTION, $rows['A']['unpatched_credits']);
        $this->assertSame([self::HOUSEHOLD_EXISTS], $rows['A']['patched_credits']);
        foreach ([$rows['A']['patched_credits'], $rows['A']['unpatched_credits']] as $credits) {
            $this->assertStringNotContainsString(self::MEMBER, implode("\n", $credits));
        }

        // F6 + F7 (D): departed, no members - old code has nothing but the deduction; new code has nothing at all.
        $this->assertSame([self::CARER_DEDUCTION], $rows['D']['unpatched_credits']);
        $this->assertSame([], $rows['D']['patched_credits']);

        // F5 (F): departed members were still credited on the old code.
        $this->assertContains('2x ' . self::MEMBER . ' (+14)', $rows['F']['unpatched_credits']);
        $this->assertStringNotContainsString(self::MEMBER, implode("\n", $rows['F']['patched_credits']));
        $this->assertStringNotContainsString(self::HOUSEHOLD_EXISTS, implode("\n", $rows['F']['unpatched_credits']));

        // Controls: identical credit lines on both sides for active (B, C) and rejoined (G) households.
        foreach (['B', 'C', 'G'] as $key) {
            $this->assertSame($rows[$key]['patched'], $rows[$key]['unpatched'], "$key must be identical");
        }

        // E: total is 0 on both, but the old breakdown carried the departed member's +7.
        $this->assertContains('1x ' . self::MEMBER . ' (+7)', $rows['E']['unpatched_credits']);
        $this->assertSame([self::CARER_DEDUCTION], $rows['E']['patched_credits']);
    }
}

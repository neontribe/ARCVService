<?php

namespace Database\Seeders;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Evaluation;
use App\Family;
use App\Note;
use App\Registration;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * On-demand manual-test fixtures for the social-prescribing evaluator fixes (audit findings F5, F6 and F7).
 *
 * NOT wired into DatabaseSeeder on purpose. Run it yourself:
 *
 *   php artisan db:seed --class="Database\\Seeders\\SocialPrescribingScenarioSeeder"
 *
 * It builds a self-contained social-prescribing sponsor (programme 1, default SP rule set: HouseholdExists +10,
 * HouseholdMember +7, DeductFromCarer -7) / centre / centre user with download rights, and one registration per
 * scenario: active and departed households with 0, 1 or 2 adult member records. It then prints a cheat-sheet of
 * the weekly entitlement each household should show on THIS branch (evaluated live) and on the unpatched code at
 * b0abd058 (modelled from the old arithmetic: carer deduction always applied, departed members still credited,
 * no clamp at zero).
 *
 * All three fixes are credits-only and social-prescribing-only: no Reminder/notice changes, and nothing here
 * depends on the calendar. Departed households (D, E, F) are hidden from the normal Store screens; read them
 * from the centre CSV export or via the "families who have left" list.
 *
 * Re-running it tears its own entities down first, so it is safe to repeat.
 *
 * The walkthrough for testers lives in docs/tests/MANUAL_TEST_SP_EVALUATOR.md.
 */
class SocialPrescribingScenarioSeeder extends Seeder
{
    public const SPONSOR_NAME = 'SP Evaluator Test Sponsor';
    public const SPONSOR_SHORTCODE = 'SPHH';
    public const CENTRE_NAME = 'SP Evaluator Test Centre';
    public const CENTRE_PREFIX = 'SPHH';
    public const USER_NAME = 'ARC SP Evaluator Tester';
    public const USER_EMAIL = 'arc+sphh@neontribe.co.uk';
    public const USER_PASSWORD = 'store_pass';

    /** Carer names all start with this so the Store search finds the whole set. */
    public const CARER_PREFIX = 'SPHH';

    /** The unpatched commit the cheat-sheet's second column models. */
    public const BASE_COMMIT = 'b0abd058';

    /** Default social-prescribing rule values (SponsorsSeeder::socialPrescribingOverrides()). */
    private const HOUSEHOLD_EXISTS = 10;
    private const HOUSEHOLD_MEMBER = 7;
    private const DEDUCT_FROM_CARER = -7;

    /** Adult member records, as in SPVoucherEvaluatorTest, so no child-age rule can ever fire. */
    private const MEMBER_DOB = '2000-01-01 00:00:00';

    private Carbon $today;

    /** Cheat-sheet rows from the last run, keyed by scenario key (also exposed for tests). */
    public array $rows = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->today = Carbon::today()->startOfDay();

        $this->tearDownPreviousRun();

        $sponsor = factory(Sponsor::class)->create([
            'name' => self::SPONSOR_NAME,
            'shortcode' => self::SPONSOR_SHORTCODE,
            'programme' => 1,
            'can_tap' => true,
        ]);
        $sponsor->evaluations()->saveMany($this->socialPrescribingRules());

        $centre = factory(Centre::class)->create([
            'name' => self::CENTRE_NAME,
            'prefix' => self::CENTRE_PREFIX,
            'sponsor_id' => $sponsor->id,
            'print_pref' => 'individual',
        ]);

        // downloader = true puts the centre CSV export tile on the dashboard; that export is the only
        // single artefact that lists departed households alongside their live entitlement.
        $user = factory(CentreUser::class)->create([
            'name' => self::USER_NAME,
            'email' => self::USER_EMAIL,
            'password' => Hash::make(self::USER_PASSWORD),
            'role' => 'centre_user',
            'downloader' => true,
        ]);
        $user->centres()->attach($centre->id, ['homeCentre' => true]);

        $this->rows = [];
        foreach ($this->scenarios() as $scenario) {
            $registration = $this->createRegistration($scenario, $centre);
            $this->rows[$scenario['key']] = $this->describe($scenario, $registration);
        }

        $this->report($this->rows);
    }

    /**
     * The scenarios. "Left" households have leaving_on three months ago; the rejoined one also has rejoin_on
     * one month ago (after leaving_on, so Family::status() is true again). Nothing here is calendar-sensitive.
     *
     * @return array[]
     */
    public function scenarios(): array
    {
        $left = $this->today->copy()->subMonths(3);
        $rejoined = $this->today->copy()->subMonths(1);

        return [
            [
                'key' => 'A',
                'title' => 'F7: active, no members',
                'note' => 'Active household with no member records. This branch 10/wk (household credit only); b0abd058 3/wk because the -7 carer deduction fired even with nobody to deduct for. NOTE: this is a live total change for every active single-person SP household - see the doc\'s follow-up section.',
                'members' => 0,
                'leaving_on' => null,
                'rejoin_on' => null,
            ],
            [
                'key' => 'B',
                'title' => 'Control: active, 1 member',
                'note' => 'Active household with one member. 10/wk on both branches (10 + 7 - 7); must NOT change.',
                'members' => 1,
                'leaving_on' => null,
                'rejoin_on' => null,
            ],
            [
                'key' => 'C',
                'title' => 'Control: active, 2 members',
                'note' => 'Active household with two members. 17/wk on both branches (10 + 14 - 7); must NOT change.',
                'members' => 2,
                'leaving_on' => null,
                'rejoin_on' => null,
            ],
            [
                'key' => 'D',
                'title' => 'F6+F7: left, no members (old code goes negative)',
                'note' => 'Departed household with no member records. This branch 0/wk; b0abd058 prints -7/wk (carer deduction with nothing to deduct from, and no clamp at zero). Only visible in the CSV export / families-left list.',
                'members' => 0,
                'leaving_on' => $left,
                'rejoin_on' => null,
            ],
            [
                'key' => 'E',
                'title' => 'Control: left, 1 member (0 on both)',
                'note' => 'Departed household with one member. 0/wk on both branches: the old code credited the departed member (+7) and deducted the carer (-7), which happens to net to zero. Total must NOT change; only the credit breakdown on /voucher-manager loses the "+7 member" line.',
                'members' => 1,
                'leaving_on' => $left,
                'rejoin_on' => null,
            ],
            [
                'key' => 'F',
                'title' => 'F5: left, 2 members',
                'note' => 'Departed household with two members. This branch 0/wk; b0abd058 7/wk because HouseholdMember read leaving_on off the Child (always null) and kept crediting departed members (14 - 7).',
                'members' => 2,
                'leaving_on' => $left,
                'rejoin_on' => null,
            ],
            [
                'key' => 'G',
                'title' => 'Control: left then rejoined, 2 members',
                'note' => 'Household that left three months ago and rejoined one month ago, two members. 17/wk on both branches; the rejoin path did not change. Must NOT change.',
                'members' => 2,
                'leaving_on' => $left,
                'rejoin_on' => $rejoined,
            ],
        ];
    }

    /**
     * Carer name for a scenario; this is what the Store search matches on.
     *
     * @param array $scenario
     * @return string
     */
    public static function carerName(array $scenario): string
    {
        return self::CARER_PREFIX . '-' . $scenario['key'] . ' ' . $scenario['title'];
    }

    /**
     * The default social-prescribing rule set, copied from SponsorsSeeder::socialPrescribingOverrides() so this
     * seeder does not depend on the dev database having been seeded.
     *
     * @return Evaluation[]
     */
    private function socialPrescribingRules(): array
    {
        return [
            new Evaluation([
                'name' => 'FamilyIsPregnant',
                'value' => null,
                'purpose' => 'credits',
                'entity' => 'App\Family',
            ]),
            new Evaluation([
                'name' => 'ChildIsBetweenOneAndPrimarySchoolAge',
                'value' => null,
                'purpose' => 'credits',
                'entity' => 'App\Child',
            ]),
            new Evaluation([
                'name' => 'ChildIsUnderOne',
                'value' => null,
                'purpose' => 'credits',
                'entity' => 'App\Child',
            ]),
            new Evaluation([
                'name' => 'ChildIsPrimarySchoolAge',
                'value' => null,
                'purpose' => 'disqualifiers',
                'entity' => 'App\Child',
            ]),
            new Evaluation([
                'name' => 'DeductFromCarer',
                'value' => self::DEDUCT_FROM_CARER,
                'purpose' => 'credits',
                'entity' => 'App\Family',
            ]),
            new Evaluation([
                'name' => 'HouseholdMember',
                'value' => self::HOUSEHOLD_MEMBER,
                'purpose' => 'credits',
                'entity' => 'App\Child',
            ]),
            new Evaluation([
                'name' => 'HouseholdExists',
                'value' => self::HOUSEHOLD_EXISTS,
                'purpose' => 'credits',
                'entity' => 'App\Family',
            ]),
            new Evaluation([
                'name' => 'ChildIsAlmostPrimarySchoolAge',
                'value' => null,
                'purpose' => 'notices',
                'entity' => 'App\Child',
            ]),
            new Evaluation([
                'name' => 'ChildIsAlmostOne',
                'value' => null,
                'purpose' => 'notices',
                'entity' => 'App\Child',
            ]),
        ];
    }

    /**
     * Build family, carer, member records and registration for one scenario.
     *
     * @param array $scenario
     * @param Centre $centre
     * @return Registration
     */
    private function createRegistration(array $scenario, Centre $centre): Registration
    {
        $family = factory(Family::class)->make();
        $family->lockToCentre($centre);
        if ($scenario['leaving_on'] !== null) {
            /** @var Carbon $leavingOn */
            $leavingOn = $scenario['leaving_on'];
            $family->leaving_on = $leavingOn->toDateTimeString();
            $family->leaving_reason = 'Manual test kit';
        }
        if ($scenario['rejoin_on'] !== null) {
            /** @var Carbon $rejoinOn */
            $rejoinOn = $scenario['rejoin_on'];
            $family->rejoin_on = $rejoinOn->toDateTimeString();
        }
        $family->save();

        $family->carers()->save(factory(Carer::class)->make(['name' => self::carerName($scenario)]));

        for ($i = 0; $i < $scenario['members']; $i++) {
            $family->children()->save(new Child([
                'born' => true,
                'dob' => self::MEMBER_DOB,
                'verified' => null,
                'deferred' => false,
            ]));
        }

        $registration = new Registration([
            'eligibility_hsbs' => 'healthy-start-receiving',
            'eligibility_nrpf' => 'no',
            'consented_on' => Carbon::now(),
            'eligible_from' => Carbon::now(),
        ]);
        $registration->centre()->associate($centre);
        $registration->family()->associate($family);
        $registration->save();

        return $registration;
    }

    /**
     * Remove everything a previous run created so the seeder is idempotent.
     */
    private function tearDownPreviousRun(): void
    {
        $sponsor = Sponsor::withTrashed()->where('shortcode', self::SPONSOR_SHORTCODE)->first();

        if ($sponsor) {
            $centres = Centre::withTrashed()->where('sponsor_id', $sponsor->id)->get();
            foreach ($centres as $centre) {
                $registrations = Registration::where('centre_id', $centre->id)->get();
                foreach ($registrations as $registration) {
                    // Free any vouchers a tester allocated, then drop bundles and the family tree.
                    $bundleIds = Bundle::where('registration_id', $registration->id)->pluck('id');
                    Voucher::withTrashed()->whereIn('bundle_id', $bundleIds)->update(['bundle_id' => null]);
                    Bundle::whereIn('id', $bundleIds)->delete();

                    $family = $registration->family;
                    if ($family) {
                        Child::where('family_id', $family->id)->delete();
                        Carer::withTrashed()->where('family_id', $family->id)->forceDelete();
                        Note::where('family_id', $family->id)->delete();
                    }
                    $registration->delete();
                    $family?->delete();
                }

                DB::table('centre_centre_user')->where('centre_id', $centre->id)->delete();
                $centre->forceDelete();
            }

            Evaluation::where('sponsor_id', $sponsor->id)->delete();
            $sponsor->forceDelete();
        }

        CentreUser::withTrashed()->where('email', self::USER_EMAIL)->get()->each(function (CentreUser $user) {
            $user->centres()->detach();
            $user->forceDelete();
        });
    }

    /**
     * One cheat-sheet row: what this branch says now, and what b0abd058 would say.
     *
     * "This branch" is evaluated live. The b0abd058 column is a small arithmetic port of only the three rules
     * that changed (see unpatchedCredits()); no notice or disqualifier can fire for adult member records, so
     * nothing else needs modelling.
     *
     * @param array $scenario
     * @param Registration $registration
     * @return array
     */
    private function describe(array $scenario, Registration $registration): array
    {
        $registration = Registration::withFullFamily()->find($registration->id);
        $valuation = $registration->getValuation();
        $active = $registration->family->status();

        $patchedCredits = array_map(
            fn($c) => $this->formatCredit($c['count'], $c['entity'] . '|' . $c['reason'], $c['reason_vouchers']),
            $valuation->getCreditReasons()
        );
        $unpatchedCredits = $this->unpatchedCredits($active, $scenario['members']);

        $entitlement = $valuation->getEntitlement();
        $unpatchedEntitlement = $this->unpatchedEntitlement($active, $scenario['members']);

        $patched = $this->formatOutcome($entitlement, $patchedCredits);
        $unpatched = $this->formatOutcome($unpatchedEntitlement, $unpatchedCredits);

        if ($scenario['leaving_on'] === null) {
            $status = 'active';
        } elseif ($active) {
            $status = 'rejoined';
        } else {
            $status = 'left';
        }

        return [
            'key' => $scenario['key'],
            'registration_id' => $registration->id,
            'rvid' => $registration->family->rvid,
            'carer' => self::carerName($scenario),
            'members' => $scenario['members'],
            'status' => $status,
            'entitlement' => $entitlement,
            'unpatched_entitlement' => $unpatchedEntitlement,
            'patched_credits' => array_values($patchedCredits),
            'unpatched_credits' => array_values($unpatchedCredits),
            'patched' => $patched,
            'unpatched' => $unpatched,
            // The observable is the weekly total (CSV "Entitlement", voucher-manager header); credit lines are
            // shown for context. Row E's breakdown differs (old code showed +7/-7) but its total is 0 on both.
            'differs' => $entitlement === $unpatchedEntitlement ? '' : 'YES',
            'note' => $scenario['note'],
        ];
    }

    /**
     * @param int $count
     * @param string $reason entity|reason as the evaluations emit it (DeductFromCarer has an empty reason)
     * @param int $vouchers
     * @return string
     */
    private function formatCredit(int $count, string $reason, int $vouchers): string
    {
        return sprintf('%dx %s (%+d)', $count, $reason, $vouchers);
    }

    /**
     * @param int $entitlement
     * @param string[] $credits
     * @return string
     */
    private function formatOutcome(int $entitlement, array $credits): string
    {
        sort($credits);
        $lines = [$entitlement . '/wk'];
        foreach ($credits as $credit) {
            $lines[] = '+ ' . $credit;
        }
        return implode("\n", $lines);
    }

    /*
     |--------------------------------------------------------------------------
     | Model of the UNPATCHED credits (commit b0abd058)
     |--------------------------------------------------------------------------
     | Only three things differed, and each is pure arithmetic:
     |  - DeductFromCarer used `$candidate->has('children')`, which is always truthy, so the carer deduction
     |    fired on every family, member records or not (F7).
     |  - HouseholdMember read leaving_on/rejoin_on off the Child, where they are always null, so every member
     |    record earned credit whether or not the family had left (F5).
     |  - Valuation::getEntitlement() returned the raw array_sum with no clamp at zero (F6).
     | HouseholdExists behaved the same on both sides (active families only). Nothing else is modelled.
     */

    /**
     * Old total: (active ? HouseholdExists : 0) + members x HouseholdMember + DeductFromCarer, unclamped.
     */
    private function unpatchedEntitlement(bool $active, int $members): int
    {
        return ($active ? self::HOUSEHOLD_EXISTS : 0)
            + $members * self::HOUSEHOLD_MEMBER
            + self::DEDUCT_FROM_CARER;
    }

    /**
     * Old credit lines, in the same format as the live column.
     *
     * @param bool $active
     * @param int $members
     * @return string[]
     */
    private function unpatchedCredits(bool $active, int $members): array
    {
        $credits = [];
        if ($active) {
            $credits[] = $this->formatCredit(1, 'Family|exists', self::HOUSEHOLD_EXISTS);
        }
        if ($members > 0) {
            $credits[] = $this->formatCredit($members, 'Child|member of the household', $members * self::HOUSEHOLD_MEMBER);
        }
        $credits[] = $this->formatCredit(1, 'Family|', self::DEDUCT_FROM_CARER);
        return $credits;
    }

    /**
     * Print the cheat-sheet (no-op when there is no console, e.g. under PHPUnit).
     *
     * @param array $rows
     */
    private function report(array $rows): void
    {
        if (!$this->command) {
            return;
        }

        $differing = implode(', ', array_keys(array_filter($rows, fn($row) => $row['differs'] === 'YES')));
        $controls = implode(', ', array_keys(array_filter($rows, fn($row) => $row['differs'] === '')));
        $departed = implode(', ', array_keys(array_filter($rows, fn($row) => $row['status'] === 'left')));

        $this->command->newLine();
        $this->command->info(sprintf(
            'Social-prescribing evaluator scenarios seeded for %s (sponsor %s, programme 1, rules +%d / +%d / %d).',
            $this->today->toFormattedDateString(),
            self::SPONSOR_SHORTCODE,
            self::HOUSEHOLD_EXISTS,
            self::HOUSEHOLD_MEMBER,
            self::DEDUCT_FROM_CARER
        ));
        $this->command->line(sprintf(
            'Store login: %s / %s   Centre: %s   Search families for "%s".',
            self::USER_EMAIL,
            self::USER_PASSWORD,
            self::CENTRE_NAME,
            self::CARER_PREFIX
        ));
        $this->command->line(sprintf(
            'Rows %s differ between branches; rows %s are controls and must be identical on both.',
            $differing,
            $controls
        ));
        $this->command->line('Only the weekly total and credit lines move; there are no Reminder/notice changes in this kit.');
        $this->command->line(sprintf(
            'Departed households (%s) are hidden from the normal registration list: read them from the dashboard centre CSV export ("Entitlement" column), or tick "show families who have left", click View and swap /view for /voucher-manager in the URL.',
            $departed
        ));
        $this->command->line(sprintf(
            'Columns: "This branch" is evaluated live; "%s" is modelled from the old carer-deduction / departed-member / no-clamp arithmetic.',
            self::BASE_COMMIT
        ));
        $this->command->newLine();

        $this->command->table(
            ['Family', 'RVID', 'Members', 'Status', 'This branch', self::BASE_COMMIT . ' (unpatched)', 'Differs?'],
            array_map(fn($row) => [
                $row['key'],
                $row['rvid'],
                $row['members'],
                $row['status'],
                $row['patched'],
                $row['unpatched'],
                $row['differs'],
            ], $rows)
        );

        $this->command->newLine();
        foreach ($rows as $row) {
            $this->command->line(sprintf('<comment>%s</comment>', $row['carer']));
            $this->command->line('    ' . $row['note']);
        }
        $this->command->newLine();
        $this->command->warn(sprintf(
            'Row A is a real production change: every ACTIVE single-person SP household moves from %d to %d per week on this branch. Raise it with the sponsors before release.',
            $this->unpatchedEntitlement(true, 0),
            self::HOUSEHOLD_EXISTS
        ));
        $this->command->newLine();
    }
}

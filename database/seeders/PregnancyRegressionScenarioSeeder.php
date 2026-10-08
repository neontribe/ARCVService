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
use App\Services\VoucherEvaluator\Valuation;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * On-demand manual-test fixtures for the pregnancy definition consolidation (audit finding F15).
 *
 * NOT wired into DatabaseSeeder on purpose. Run it yourself:
 *
 *   php artisan db:seed --class="Database\\Seeders\\PregnancyRegressionScenarioSeeder"
 *
 * This is a NO-REGRESSION kit. The F15 change (Family::isPregnant(), a deterministic Family::expecting,
 * FamilyIsPregnant and other_info.blade.php sharing one predicate) alters nothing a Store user can see, so
 * every family seeded here is a control: entitlement, credit wording, Reminder box and the "including one
 * pregnancy" line must be identical on this branch and on the unpatched code at be27ecd6.
 *
 * It builds a self-contained sponsor (plain default rules, no overrides) / centre / centre user and one
 * registration per scenario, then prints a cheat-sheet of what each family shows on THIS branch (evaluated
 * live) and on be27ecd6 (modelled from the old "last unborn child" expecting loop and its truthy check).
 * The only real difference, the internal due date on a multiple pregnancy, is printed in its own column so
 * a tester can see that it exists and that it is not displayed anywhere in the Store.
 *
 * Re-running it tears its own entities down first, so it is safe to repeat.
 *
 * The walkthrough for testers lives in docs/tests/MANUAL_TEST_PREGNANCY_REGRESSION.md.
 */
class PregnancyRegressionScenarioSeeder extends Seeder
{
    public const SPONSOR_NAME = 'Pregnancy Regression Test Sponsor';
    public const SPONSOR_SHORTCODE = 'PREG';
    public const CENTRE_NAME = 'Pregnancy Regression Test Centre';
    public const CENTRE_PREFIX = 'PREG';
    public const USER_NAME = 'ARC Pregnancy Regression Tester';
    public const USER_EMAIL = 'arc+preg@neontribe.co.uk';
    public const USER_PASSWORD = 'store_pass';

    /** Carer names all start with this so the Store search finds the whole set. */
    public const CARER_PREFIX = 'PREG';

    /** The unpatched commit the cheat-sheet models. */
    public const BASE_COMMIT = 'be27ecd6';

    /** Default-rule FamilyIsPregnant weight (EvaluatorFactory), used only by the unpatched model. */
    private const PREGNANCY_CREDIT = 4;

    /** Credit reason string FamilyIsPregnant emits. */
    private const PREGNANCY_REASON = 'pregnant';

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

        // Plain default rule set: no evaluations rows at all, the common England/Wales production shape.
        $sponsor = factory(Sponsor::class)->create([
            'name' => self::SPONSOR_NAME,
            'shortcode' => self::SPONSOR_SHORTCODE,
            'can_tap' => true,
        ]);

        $centre = factory(Centre::class)->create([
            'name' => self::CENTRE_NAME,
            'prefix' => self::CENTRE_PREFIX,
            'sponsor_id' => $sponsor->id,
            'print_pref' => 'individual',
        ]);

        $user = factory(CentreUser::class)->create([
            'name' => self::USER_NAME,
            'email' => self::USER_EMAIL,
            'password' => Hash::make(self::USER_PASSWORD),
            'role' => 'centre_user',
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
     * The scenarios. DOBs are the 1st of the month (as the Store always stores them) and are computed
     * relative to today, so the set stays meaningful whenever it is seeded. Nothing here depends on the
     * school start month: no child is anywhere near school age or its first birthday.
     *
     * Each child carries an explicit `born` flag because Family::isPregnant() keys off that column, not the
     * date; scenario D relies on an unborn child whose due date has passed.
     *
     * @return array[]
     */
    public function scenarios(): array
    {
        $thisMonth = $this->today->copy()->startOfMonth();

        return [
            [
                'key' => 'A',
                'title' => 'Control: pregnancy only, due in 3 months',
                'note' => 'One unborn child due in three months and no born children. 4/wk with "1x pregnant" and an empty Reminder box on both branches: a pregnancy-only household is eligible and credited.',
                'children' => [
                    ['dob' => $thisMonth->copy()->addMonths(3), 'born' => false],
                ],
            ],
            [
                'key' => 'B',
                'title' => 'Control: toddler plus pregnancy',
                'note' => '2yo plus an unborn child due in three months. 8/wk with "1x between 1 and start of primary school age" and "1x pregnant" on both branches: the pregnancy credit stacks with a child credit.',
                'children' => [
                    ['dob' => $thisMonth->copy()->subYears(2), 'born' => true],
                    ['dob' => $thisMonth->copy()->addMonths(3), 'born' => false],
                ],
            ],
            [
                'key' => 'C',
                'title' => 'Control: two unborn children, different due dates',
                'note' => 'Two unborn children due in two and five months. 4/wk with a single "1x pregnant" on both branches. This is the ONLY row with any difference at all: internally Family::expecting is now the earliest due date (+2 months) where be27ecd6 kept the last one loaded (+5 months); that date is not displayed anywhere in the Store.',
                'children' => [
                    ['dob' => $thisMonth->copy()->addMonths(2), 'born' => false],
                    ['dob' => $thisMonth->copy()->addMonths(5), 'born' => false],
                ],
            ],
            [
                'key' => 'D',
                'title' => 'Control: overdue pregnancy not yet marked born',
                'note' => 'One child recorded as unborn with a due date two months ago. 4/wk with "1x pregnant" on both branches: the credit survives the due date until the child is marked born (audit F3, by design).',
                'children' => [
                    ['dob' => $thisMonth->copy()->subMonths(2), 'born' => false],
                ],
            ],
            [
                'key' => 'E',
                'title' => 'Control: toddler only, no pregnancy',
                'note' => '3yo only. 4/wk with "1x between 1 and start of primary school age" and no pregnancy line on both branches: proves the kit works when there is nothing pregnancy-related to show.',
                'children' => [
                    ['dob' => $thisMonth->copy()->subYears(3), 'born' => true],
                ],
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
     * Build family, carer, children and registration for one scenario.
     *
     * @param array $scenario
     * @param Centre $centre
     * @return Registration
     */
    private function createRegistration(array $scenario, Centre $centre): Registration
    {
        $family = factory(Family::class)->make();
        $family->lockToCentre($centre);
        $family->save();

        $family->carers()->save(factory(Carer::class)->make(['name' => self::carerName($scenario)]));

        foreach ($scenario['children'] as $child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            $family->children()->save(new Child([
                'born' => $child['born'] ?? $dob->isPast(),
                'dob' => $dob->toDateTimeString(),
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
     * One cheat-sheet row: what this branch says now, and what be27ecd6 would say.
     *
     * Child credits, disqualifiers and notices did not change between the two commits, so they are taken
     * live for both columns. Only the family pregnancy predicate is modelled for the unpatched column, from
     * the old Family::expecting loop; the two columns are then COMPUTED and compared, not asserted equal.
     *
     * @param array $scenario
     * @param Registration $registration
     * @return array
     */
    private function describe(array $scenario, Registration $registration): array
    {
        $registration = Registration::withFullFamily()->find($registration->id);
        $family = $registration->family;
        $valuation = $registration->getValuation();

        $credits = array_map(fn($c) => $c['count'] . 'x ' . $c['reason'], $valuation->getCreditReasons());
        $notices = array_map(fn($n) => $n['count'] . 'x ' . $n['reason'], $valuation->getNoticeReasons());

        $isPregnant = $family->isPregnant();
        $expectingNew = $family->expecting;
        $expectingOld = $this->unpatchedExpecting($family->children);
        $unpatchedIsPregnant = $expectingOld !== null;

        [$unpatchedEntitlement, $unpatchedCredits] = $this->unpatchedOutcome(
            $valuation,
            $credits,
            $isPregnant,
            $unpatchedIsPregnant
        );

        $patched = $this->formatOutcome($valuation->getEntitlement(), $credits, $notices);
        $unpatched = $this->formatOutcome($unpatchedEntitlement, $unpatchedCredits, $notices);

        $children = array_map(function ($child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            $age = $child['born']
                ? $dob->diff($this->today)->format('%yy%mm')
                : 'P' . ($dob->isPast() ? ', overdue' : '');
            return $dob->format('M Y') . ' (' . $age . ')';
        }, $scenario['children']);

        return [
            'key' => $scenario['key'],
            'registration_id' => $registration->id,
            'rvid' => $family->rvid,
            'carer' => self::carerName($scenario),
            'children' => implode("\n", $children),
            'entitlement' => $valuation->getEntitlement(),
            'credits' => array_values($credits),
            'notices' => array_values($notices),
            'unpatched_entitlement' => $unpatchedEntitlement,
            'unpatched_credits' => array_values($unpatchedCredits),
            'is_pregnant' => $isPregnant,
            'unpatched_is_pregnant' => $unpatchedIsPregnant,
            'expecting_new' => $expectingNew?->toDateString(),
            'expecting_old' => $expectingOld?->toDateString(),
            'patched' => $patched,
            'unpatched' => $unpatched,
            'differs' => $patched === $unpatched ? '' : 'YES',
            'note' => $scenario['note'],
        ];
    }

    /**
     * @param int $entitlement
     * @param string[] $credits
     * @param string[] $notices
     * @return string
     */
    private function formatOutcome(int $entitlement, array $credits, array $notices): string
    {
        sort($credits);
        sort($notices);
        $lines = [$entitlement . '/wk'];
        foreach ($credits as $credit) {
            $lines[] = '+ ' . $credit;
        }
        foreach ($notices as $notice) {
            $lines[] = '! ' . $notice;
        }
        return implode("\n", $lines);
    }

    /*
     |--------------------------------------------------------------------------
     | Model of the UNPATCHED pregnancy predicate (commit be27ecd6)
     |--------------------------------------------------------------------------
     | A faithful port of Family::getExpectingAttribute() as it stood before F15: loop over the children in
     | load order and keep the dob of the LAST unborn one. FamilyIsPregnant then tested that value for
     | truthiness, and other_info.blade.php tested it against null. children.dob is NOT NULL, so the old
     | predicate is simply "expecting !== null". Nothing else changed, so nothing else is modelled.
     */

    /**
     * Old Family::expecting: the dob of the last unborn child in the loaded collection, or null.
     *
     * @param Collection $children
     * @return Carbon|null
     */
    private function unpatchedExpecting(Collection $children): ?Carbon
    {
        $due = null;
        foreach ($children as $child) {
            if (!$child->born) {
                $due = $child->dob;
            }
        }
        return $due;
    }

    /**
     * Old entitlement and credit list: the live figures with the pregnancy credit swapped for the one the
     * old predicate would have awarded under the default FamilyIsPregnant weight.
     *
     * @param Valuation $valuation
     * @param string[] $credits
     * @param bool $isPregnant
     * @param bool $unpatchedIsPregnant
     * @return array{0: int, 1: string[]}
     */
    private function unpatchedOutcome(
        Valuation $valuation,
        array $credits,
        bool $isPregnant,
        bool $unpatchedIsPregnant
    ): array {
        $entitlement = $valuation->getEntitlement();
        $credits = array_values(array_filter(
            $credits,
            fn($credit) => !str_ends_with($credit, 'x ' . self::PREGNANCY_REASON)
        ));

        if ($isPregnant) {
            $entitlement -= self::PREGNANCY_CREDIT;
        }
        if ($unpatchedIsPregnant) {
            $entitlement += self::PREGNANCY_CREDIT;
            $credits[] = '1x ' . self::PREGNANCY_REASON;
        }

        return [$entitlement, $credits];
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

        $this->command->newLine();
        $this->command->info(sprintf(
            'Pregnancy regression scenarios seeded for %s.',
            $this->today->toFormattedDateString()
        ));
        $this->command->line(sprintf(
            'Store login: %s / %s   Centre: %s   Search families for "%s".',
            self::USER_EMAIL,
            self::USER_PASSWORD,
            self::CENTRE_NAME,
            self::CARER_PREFIX
        ));
        $this->command->line(sprintf(
            '<comment>Every row is a control: "Differs?" must be blank on this branch AND on %s.</comment>',
            self::BASE_COMMIT
        ));
        $this->command->line('Only the internal due date on family C differs (earliest vs last unborn child) and it is not displayed in the Store.');
        $this->command->line(sprintf(
            'Columns: "This branch" is evaluated live; "%s" is modelled from the old Family::expecting loop.',
            self::BASE_COMMIT
        ));
        $this->command->line('Re-run this seeder after changing the date to refresh the expectations.');
        $this->command->newLine();

        $this->command->table(
            ['Family', 'RVID', 'Children', 'This branch', self::BASE_COMMIT . ' (unpatched)', 'Due date (new / old)', 'Differs?'],
            array_map(fn($row) => [
                $row['key'],
                $row['rvid'],
                $row['children'],
                $row['patched'],
                $row['unpatched'],
                ($row['expecting_new'] ?? '-') . ' / ' . ($row['expecting_old'] ?? '-'),
                $row['differs'],
            ], $rows)
        );

        $this->command->newLine();
        foreach ($rows as $row) {
            $this->command->line(sprintf('<comment>%s</comment>', $row['carer']));
            $this->command->line('    ' . $row['note']);
        }
        $this->command->newLine();
    }
}

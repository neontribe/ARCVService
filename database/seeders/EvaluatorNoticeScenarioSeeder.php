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
 * On-demand manual-test fixtures for the evaluator notice fixes (audit findings F1 and F2).
 *
 * NOT wired into DatabaseSeeder on purpose. Run it yourself:
 *
 *   php artisan db:seed --class="Database\\Seeders\\EvaluatorNoticeScenarioSeeder"
 *
 * It builds a self-contained sponsor (plain default rules, no overrides) / centre / centre user and one
 * registration per scenario, then prints a cheat-sheet of what each family should show on THIS branch
 * (evaluated live) and on the unpatched code at 1f019ce7 (modelled from the old "almost" arithmetic and
 * the swallowed disqualifier reasons), for today's date and the current ARC_SCHOOL_MONTH.
 *
 * Both fixes are notice-only: no voucher total moves on any family, only the Reminder box does.
 * Re-running it tears its own entities down first, so it is safe to repeat.
 *
 * The walkthrough for testers lives in docs/tests/MANUAL_TEST_EVALUATOR_NOTICES.md.
 */
class EvaluatorNoticeScenarioSeeder extends Seeder
{
    public const SPONSOR_NAME = 'Evaluator Notices Test Sponsor';
    public const SPONSOR_SHORTCODE = 'NOTE';
    public const CENTRE_NAME = 'Evaluator Notices Test Centre';
    public const CENTRE_PREFIX = 'NOTE';
    public const USER_NAME = 'ARC Evaluator Notices Tester';
    public const USER_EMAIL = 'arc+note@neontribe.co.uk';
    public const USER_PASSWORD = 'store_pass';

    /** Carer names all start with this so the Store search finds the whole set. */
    public const CARER_PREFIX = 'NOTE';

    /** The real England/Wales school start month; DOBs are anchored to this regardless of the env override. */
    private const SEPTEMBER = 9;

    private Carbon $today;

    /** Configured school start month (ARC_SCHOOL_MONTH), used for the cheat-sheet only. */
    private int $schoolMonth;

    /** Year of the most recent September school start on or before today. */
    private int $schoolYear;

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
        $this->schoolMonth = (int) config('arc.school_month', self::SEPTEMBER);
        $this->schoolYear = ($this->today->month >= self::SEPTEMBER)
            ? $this->today->year
            : $this->today->year - 1;

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
     * relative to the current September school year so the set stays meaningful whenever it is seeded.
     *
     * Y = year of the most recent September start (e.g. seeded Sep 2026 -> Y = 2026). Scenario C is instead
     * anchored to 1 September of the current calendar year: a January-born child of (this year - 4) belongs
     * to that cohort, and because January precedes every other month the env lever ARC_SCHOOL_MONTH=<m+2>
     * always lands on a future start date in the current year (Jan-Oct).
     *
     * @return array[]
     */
    public function scenarios(): array
    {
        $y = $this->schoolYear;
        $thisMonth = $this->today->copy()->startOfMonth();

        return [
            [
                'key' => 'A',
                'title' => 'F1: at-school child disqualified with a visible reason',
                'note' => '6yo (Jun Y-6) plus 2yo (Jun Y-2). 4/wk on both branches; the fix adds the Reminder "A Child is primary school age", the old code shows no Reminder at all. Visible in every month.',
                'env' => 'any',
                'children' => [
                    ['dob' => Carbon::create($y - 6, 6, 1), 'deferred' => false],
                    ['dob' => Carbon::create($y - 2, 6, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'B',
                'title' => 'F1: zero vouchers, and the fix says why',
                'note' => 'Only child 6yo (Jun Y-6). 0/wk on both branches; the fix shows "A Child is primary school age", the old code shows 0 and nothing else. Visible in every month.',
                'env' => 'any',
                'children' => [
                    ['dob' => Carbon::create($y - 6, 6, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'C',
                'title' => 'F2: "almost primary school age" window',
                'note' => 'Child born January four years ago (starts, or started, school on 1 September THIS calendar year) plus a 6-month-old. With the default month the fix shows "almost primary school age" in August AND September while the old code shows it in July AND August (two months early, never in the start month). With ARC_SCHOOL_MONTH = this month + 2 the OLD code shows it two months early and the fix stays quiet (entitlement rises equally on both); ARC_SCHOOL_MONTH = this month + 1 is the control where both show it.',
                'env' => 'ARC_SCHOOL_MONTH=9 / <this month + 2> / <this month + 1>',
                'children' => [
                    ['dob' => Carbon::create($this->today->year - 4, 1, 1), 'deferred' => false],
                    ['dob' => $thisMonth->copy()->subMonths(6), 'deferred' => false],
                ],
            ],
            [
                'key' => 'D',
                'title' => 'Control: almost 1 year old (no difference)',
                'note' => '11-month-old only child. Both branches show "almost 1 year old": for 1st-of-month DOBs the old and new ChildIsAlmostOne maths agree, so this row must NOT change.',
                'env' => 'any',
                'children' => [
                    ['dob' => $thisMonth->copy()->subMonths(11), 'deferred' => false],
                ],
            ],
            [
                'key' => 'E',
                'title' => 'Control: ordinary family, no notices',
                'note' => '3yo (Jun Y-3) plus a pregnancy due in three months. 8/wk and an empty Reminder box on both branches.',
                'env' => 'any',
                'children' => [
                    ['dob' => Carbon::create($y - 3, 6, 1), 'deferred' => false],
                    ['dob' => $thisMonth->copy()->addMonths(3), 'deferred' => false],
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
                'born' => $dob->isPast(),
                'dob' => $dob->toDateTimeString(),
                'verified' => null,
                'deferred' => $child['deferred'],
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
     * One cheat-sheet row: what this branch says now, and what 1f019ce7 would say.
     *
     * Entitlement and credit reasons are identical on both branches by construction (the fixes only touch
     * notice specs and the notice display path), so both columns take them live and only the notices differ.
     *
     * @param array $scenario
     * @param Registration $registration
     * @return array
     */
    private function describe(array $scenario, Registration $registration): array
    {
        $registration = Registration::withFullFamily()->find($registration->id);
        $valuation = $registration->getValuation();

        $credits = array_map(fn($c) => $c['count'] . 'x ' . $c['reason'], $valuation->getCreditReasons());
        $patchedNotices = array_map(fn($n) => $n['count'] . 'x ' . $n['reason'], $valuation->getNoticeReasons());
        $unpatchedNotices = $this->unpatchedNotices($scenario['children']);

        $patched = $this->formatOutcome($valuation->getEntitlement(), $credits, $patchedNotices);
        $unpatched = $this->formatOutcome($valuation->getEntitlement(), $credits, $unpatchedNotices);

        $children = array_map(function ($child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            $age = $dob->isFuture()
                ? 'P'
                : $dob->diff($this->today)->format('%yy%mm');
            return $dob->format('M Y') . ' (' . $age . ')' . ($child['deferred'] ? ' deferred' : '');
        }, $scenario['children']);

        return [
            'key' => $scenario['key'],
            'registration_id' => $registration->id,
            'rvid' => $registration->family->rvid,
            'carer' => self::carerName($scenario),
            'children' => implode("\n", $children),
            'env' => $scenario['env'],
            'entitlement' => $valuation->getEntitlement(),
            'patched_notices' => array_values($patchedNotices),
            'unpatched_notices' => array_values($unpatchedNotices),
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
     | Model of the UNPATCHED notices (commit 1f019ce7)
     |--------------------------------------------------------------------------
     | A faithful, compressed port of IsAlmostYears and IsAlmostStartDate as they stood before the F2 fix
     | ("(int) diffInMonths(target) <= 1 && target->isFuture()"), plus the F1 behaviour that
     | Valuation::getNoticeReasons() read the non-existent 'disqualifications' bucket and therefore never
     | surfaced a disqualifier reason. Credits and entitlement did not change, so they are not modelled.
     */

    /**
     * Old IsAlmostYears: target is the END of the birth month plus N years.
     */
    private function unpatchedIsAlmostYears(Carbon $dob, int $years): bool
    {
        $target = $dob->copy()->endOfMonth()->addYears($years);
        return $this->unpatchedWindowOpen($target);
    }

    /**
     * Old IsAlmostStartDate: target from Child::calcFutureMonthYear($years, $month), which is unchanged.
     */
    private function unpatchedIsAlmostStartDate(Carbon $dob, int $years, int $month): bool
    {
        $years = ($dob->month < $month) ? $years - 1 : $years;
        $target = Carbon::createFromDate($dob->copy()->addYears($years)->year, $month, 1)->startOfDay();
        return $this->unpatchedWindowOpen($target);
    }

    /** The old guard: target still ahead of us and fewer than two whole months away (truncated). */
    private function unpatchedWindowOpen(Carbon $target): bool
    {
        return $target->isFuture() && (int) $this->today->diffInMonths($target) <= 1;
    }

    /**
     * Old notice list for a family under the default rule set: only the two "almost" notices for born
     * children, never a disqualifier reason.
     *
     * @param array $children
     * @return string[]
     */
    private function unpatchedNotices(array $children): array
    {
        $notices = [];

        foreach ($children as $child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            if ($dob->isFuture()) {
                continue;
            }

            if ($this->unpatchedIsAlmostYears($dob, 1)) {
                $notices['almost 1 year old'] = ($notices['almost 1 year old'] ?? 0) + 1;
            }
            if ($this->unpatchedIsAlmostStartDate($dob, 5, $this->schoolMonth)) {
                $notices['almost primary school age'] = ($notices['almost primary school age'] ?? 0) + 1;
            }
        }

        $lines = [];
        foreach ($notices as $reason => $count) {
            $lines[] = $count . 'x ' . $reason;
        }
        return $lines;
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
            'Evaluator notice scenarios seeded for %s with ARC_SCHOOL_MONTH=%d (school year Sep %d).',
            $this->today->toFormattedDateString(),
            $this->schoolMonth,
            $this->schoolYear
        ));
        $this->command->line(sprintf(
            'Store login: %s / %s   Centre: %s   Search families for "%s".',
            self::USER_EMAIL,
            self::USER_PASSWORD,
            self::CENTRE_NAME,
            self::CARER_PREFIX
        ));
        $this->command->line('Only the Reminder box ("!" lines) differs between branches; entitlement and credit lines are identical by design.');
        $this->command->line('Columns: "This branch" is evaluated live; "1f019ce7" is modelled from the old notice arithmetic.');
        $this->command->line('Re-run this seeder after changing ARC_SCHOOL_MONTH or the date to refresh the expectations.');
        $this->command->newLine();

        $this->command->table(
            ['Family', 'RVID', 'Children', 'Env', 'This branch', '1f019ce7 (unpatched)', 'Differs?'],
            array_map(fn($row) => [
                $row['key'],
                $row['rvid'],
                $row['children'],
                $row['env'],
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
    }
}

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
 * On-demand manual-test fixtures for the Scottish voucher evaluator fixes (audit findings F8-F12).
 *
 * NOT wired into DatabaseSeeder on purpose. Run it yourself:
 *
 *   php artisan db:seed --class="Database\\Seeders\\ScottishEvaluatorScenarioSeeder"
 *
 * It builds a self-contained sponsor / centre / centre user and one registration per scenario, then prints a
 * cheat-sheet of what each family should show on THIS branch (evaluated live) and on the unpatched code at
 * ae14917c (modelled from the old month arithmetic), for today's date and the current
 * ARC_SCOTTISH_SCHOOL_MONTH. Re-running it tears its own entities down first, so it is safe to repeat.
 *
 * The walkthrough for testers lives in docs/tests/MANUAL_TEST_SCOTTISH_EVALUATOR.md.
 */
class ScottishEvaluatorScenarioSeeder extends Seeder
{
    public const SPONSOR_NAME = 'Scottish Evaluator Test Sponsor';
    public const SPONSOR_SHORTCODE = 'SCOT';
    public const CENTRE_NAME = 'Scottish Evaluator Test Centre';
    public const CENTRE_PREFIX = 'SCOT';
    public const USER_NAME = 'ARC Scottish Evaluator Tester';
    public const USER_EMAIL = 'arc+scot@neontribe.co.uk';
    public const USER_PASSWORD = 'store_pass';

    /** Carer names all start with this so the Store search finds the whole set. */
    public const CARER_PREFIX = 'SCOT';

    /** The real Scottish school start month; DOBs are anchored to this regardless of the env override. */
    private const AUGUST = 8;

    private Carbon $today;

    /** Configured school start month (ARC_SCOTTISH_SCHOOL_MONTH), used for the cheat-sheet only. */
    private int $schoolMonth;

    /** Year of the most recent August school start on or before today. */
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
        $this->schoolMonth = (int) config('arc.scottish_school_month', self::AUGUST);
        $this->schoolYear = ($this->today->month >= self::AUGUST)
            ? $this->today->year
            : $this->today->year - 1;

        $this->tearDownPreviousRun();

        $sponsor = factory(Sponsor::class)->create([
            'name' => self::SPONSOR_NAME,
            'shortcode' => self::SPONSOR_SHORTCODE,
            'can_tap' => true,
        ]);
        // Same rule set as the "Scottish Rules Project" dev sponsor, minus ID verification so the
        // Reminder box only ever shows the Scottish notices we are testing.
        $sponsor->evaluations()->saveMany((new SponsorsSeeder())->scottishFamilyOverrides());

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
     * relative to the current August school year so the set stays meaningful whenever it is seeded.
     *
     * Y = year of the most recent August start (e.g. seeded Sep 2026 -> Y = 2026).
     *
     * @return array[]
     */
    public function scenarios(): array
    {
        $y = $this->schoolYear;

        return [
            [
                'key' => 'A',
                'title' => 'F9/F10 under-credit: 4yo wrongly treated as at school',
                'note' => 'Only child, born May Y-4, starts school Aug Y+1. Old code calls any 4y1m+ child "at school" from Sep to Dec, so the family is disqualified (0). Same on both branches Jan-Aug.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=8',
                'children' => [
                    ['dob' => Carbon::create($y - 4, 5, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'B',
                'title' => 'F11: deferred child loses credit on 5th birthday',
                'note' => 'Only child, born May Y-5, deferred, so actually starts Aug Y+1. Old code checks "age >= 5" before it looks at the deferred flag. Visible in every month.',
                'env' => 'any',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 5, 1), 'deferred' => true],
                ],
            ],
            [
                'key' => 'C',
                'title' => 'Control: deferral honoured while still 4',
                'note' => 'Only child, born Oct Y-5, deferred. Both branches credit until the 5th birthday in October; after that this becomes a second F11 case.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=8',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 10, 1), 'deferred' => true],
                ],
            ],
            [
                'key' => 'D',
                'title' => 'Control: genuinely at school plus eligible sibling',
                'note' => 'Elder born Oct Y-5 (started Aug Y), toddler born Jun Y-2. 8/wk on both branches; the elder reads "primary school age (SCOTLAND)".',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=8',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 10, 1), 'deferred' => false],
                    ['dob' => Carbon::create($y - 2, 6, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'E',
                'title' => 'F9 over-credit: at-school 4yo still credited (needs env)',
                'note' => 'Only child born Feb Y-4, a Jan/Feb starter who began school Aug Y at 4y6m. With ARC_SCOTTISH_SCHOOL_MONTH set to the CURRENT month the old code says no 4yo is at school (4/wk) while the fix disqualifies the family (0). Identical with the default 8.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=<current month>',
                'children' => [
                    ['dob' => Carbon::create($y - 4, 2, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'F',
                'title' => 'Control: only child too old',
                'note' => '7-year-old only child. 0/wk on both branches.',
                'env' => 'any',
                'children' => [
                    ['dob' => Carbon::create($y - 7, 6, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'G',
                'title' => 'Control: pregnancy only',
                'note' => 'Due in three months. 4/wk on both branches.',
                'env' => 'any',
                'children' => [
                    ['dob' => $this->today->copy()->startOfMonth()->addMonths(3), 'deferred' => false],
                ],
            ],
            [
                'key' => 'H1',
                'title' => 'Notices: old fires on age string alone (needs env)',
                'note' => 'Only child born Jun Y-4 (4y3m in Sep), starts Aug Y+1. With ARC_SCOTTISH_SCHOOL_MONTH = next month the old code shows "almost primary school age" AND "able to defer"; the fix shows nothing because school is a year away.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=<next month>',
                'children' => [
                    ['dob' => Carbon::create($y - 4, 6, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'H2',
                'title' => 'Notices: fix uses the real start cohort (needs env)',
                'note' => 'Only child born Apr Y-5 (5y5m in Sep). With ARC_SCOTTISH_SCHOOL_MONTH = next month the fix shows "almost primary school age" (no defer, already 5); the old code shows nothing because 5y5m is outside its 4y1m-5y0m string check.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=<next month>',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 4, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'H3',
                'title' => 'Notices: defer eligibility is "under 5 at start" (needs env)',
                'note' => 'Only child born Nov Y-5 (4y10m in Sep). With ARC_SCOTTISH_SCHOOL_MONTH = next month both show "almost primary school age", but only the fix adds "able to defer" (old code caps deferral at 4y6m).',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=<next month>',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 11, 1), 'deferred' => false],
                ],
            ],
            [
                'key' => 'H4',
                'title' => 'Notices: already-deferred child is left alone (needs env)',
                'note' => 'Only child born Dec Y-5 (4y9m in Sep), deferred. With ARC_SCOTTISH_SCHOOL_MONTH = next month the old code still shows "almost primary school age" (it never reads the flag); the fix shows nothing because the start moved to Y+1.',
                'env' => 'ARC_SCOTTISH_SCHOOL_MONTH=<next month>',
                'children' => [
                    ['dob' => Carbon::create($y - 5, 12, 1), 'deferred' => true],
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
     * One cheat-sheet row: what this branch says now, and what ae14917c would say.
     *
     * @param array $scenario
     * @param Registration $registration
     * @return array
     */
    private function describe(array $scenario, Registration $registration): array
    {
        $registration = Registration::withFullFamily()->find($registration->id);
        $valuation = $registration->getValuation();

        $patched = $this->formatOutcome(
            $valuation->getEntitlement(),
            array_map(fn($c) => $c['count'] . 'x ' . $c['reason'], $valuation->getCreditReasons()),
            array_map(fn($n) => $n['count'] . 'x ' . $n['reason'], $valuation->getNoticeReasons())
        );

        $old = $this->unpatchedValuation($scenario['children']);
        $unpatched = $this->formatOutcome($old['entitlement'], $old['credits'], $old['notices']);

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
            'unpatched_entitlement' => $old['entitlement'],
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
     | Model of the UNPATCHED Scottish rules (commit ae14917c)
     |--------------------------------------------------------------------------
     | A faithful, compressed port of isScottishChildAtSchool(), ScottishChildIsAlmostPrimarySchoolAge
     | and ScottishChildCanDefer as they stood before the fix, so the cheat-sheet can say what the old
     | branch will show for today's date and school month without checking it out.
     */

    /**
     * Age as [years, months] from Child::getAgeString('%y,%m'), or null for a pregnancy.
     *
     * @param Carbon $dob
     * @return int[]|null
     */
    private function ageParts(Carbon $dob): ?array
    {
        if ($dob->isFuture()) {
            return null;
        }
        $diff = $dob->diff($this->today);
        return [$diff->y, $diff->m];
    }

    /**
     * Old isScottishChildAtSchool(): "age >= 5" wins before deferral is ever consulted,
     * and a 4-year-old is only "at school" while (schoolMonth - monthNow) < 0.
     */
    private function unpatchedIsAtSchool(Carbon $dob, bool $deferred): bool
    {
        $age = $this->ageParts($dob);
        if ($age === null) {
            return false;
        }
        [$year] = $age;

        if ($year >= 5) {
            return true;
        }
        if ($year < 4) {
            return false;
        }
        if ($this->schoolMonth - $this->today->month < 0) {
            return !$deferred;
        }
        return false;
    }

    /** Old notice guard: only fires when schoolMonth - monthNow is 0 or 1 (never wraps the year). */
    private function unpatchedNoticeWindowOpen(): bool
    {
        $gap = $this->schoolMonth - $this->today->month;
        return $gap >= 0 && $gap <= 1;
    }

    private function unpatchedIsAlmostPrimarySchoolAge(Carbon $dob): bool
    {
        $age = $this->ageParts($dob);
        if ($age === null || !$this->unpatchedNoticeWindowOpen()) {
            return false;
        }
        [$year, $month] = $age;
        return ($year === 4 && $month >= 1) || ($year === 5 && $month === 0);
    }

    private function unpatchedCanDefer(Carbon $dob): bool
    {
        $age = $this->ageParts($dob);
        if ($age === null || !$this->unpatchedNoticeWindowOpen()) {
            return false;
        }
        [$year, $month] = $age;
        return $year === 4 && $month >= 1 && $month <= 6;
    }

    /**
     * Old whole-family outcome under the Scottish rule set.
     *
     * @param array $children
     * @return array{entitlement:int, credits:string[], notices:string[]}
     */
    private function unpatchedValuation(array $children): array
    {
        $satisfier = false;
        $pregnant = false;
        $credits = [];
        $notices = [];

        foreach ($children as $child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            $age = $this->ageParts($dob);

            if ($age === null) {
                $pregnant = true;
                $satisfier = true;
                continue;
            }

            $atSchool = $this->unpatchedIsAtSchool($dob, $child['deferred']);
            if (!$atSchool) {
                $satisfier = true;
            }

            if ($age[0] < 1) {
                $this->addCredit($credits, 'under 1 year old', 6);
            } elseif (!$atSchool) {
                $this->addCredit($credits, 'between 1 and start of primary school age (SCOTLAND)', 4);
            } else {
                $this->addCredit($credits, 'primary school age (SCOTLAND)', 4);
            }

            if ($this->unpatchedIsAlmostPrimarySchoolAge($dob)) {
                $key = 'almost primary school age (SCOTLAND)';
                $notices[$key] = ($notices[$key] ?? 0) + 1;
            }
            if ($this->unpatchedCanDefer($dob)) {
                $key = 'able to defer (SCOTLAND)';
                $notices[$key] = ($notices[$key] ?? 0) + 1;
            }
        }

        if ($pregnant) {
            $this->addCredit($credits, 'pregnant', 4);
        }

        // Family disqualified (ScottishFamilyHasNoEligibleChildren): no credits count, notices still show.
        $creditLines = [];
        $entitlement = 0;
        if ($satisfier) {
            foreach ($credits as $reason => $credit) {
                $creditLines[] = $credit['count'] . 'x ' . $reason;
                $entitlement += $credit['value'];
            }
        }

        $noticeLines = [];
        foreach ($notices as $reason => $count) {
            $noticeLines[] = $count . 'x ' . $reason;
        }

        return [
            'entitlement' => $entitlement,
            'credits' => $creditLines,
            'notices' => $noticeLines,
        ];
    }

    /**
     * @param array $credits
     * @param string $reason
     * @param int $value
     */
    private function addCredit(array &$credits, string $reason, int $value): void
    {
        $credits[$reason] ??= ['count' => 0, 'value' => 0];
        $credits[$reason]['count']++;
        $credits[$reason]['value'] += $value;
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
            'Scottish evaluator scenarios seeded for %s with ARC_SCOTTISH_SCHOOL_MONTH=%d (school year Aug %d).',
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
        $this->command->line('Columns: "This branch" is evaluated live; "ae14917c" is modelled from the old month arithmetic.');
        $this->command->line('Re-run this seeder after changing ARC_SCOTTISH_SCHOOL_MONTH or the date to refresh the expectations.');
        $this->command->newLine();

        $this->command->table(
            ['Family', 'RVID', 'Children', 'Env', 'This branch', 'ae14917c (unpatched)', 'Differs?'],
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

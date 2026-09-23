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
 * On-demand manual-test fixtures for the negative Child credit fix (audit finding F16).
 *
 * NOT wired into DatabaseSeeder on purpose. Run it yourself:
 *
 *   php artisan db:seed --class="Database\\Seeders\\NegativeChildCreditScenarioSeeder"
 *
 * F16 is latent in production: no live rule set gives a Child rule a negative value, so a standard sponsor
 * looks identical on both sides of the fix. This seeder therefore builds its own sponsor whose rule set
 * turns ChildIsPrimarySchoolAge into a "deduct 2 per school-age child" credit (and switches the standard
 * disqualifier off), then one registration per scenario, and prints a cheat-sheet of what each family
 * should show on THIS branch (evaluated live) and on the unpatched code at 48331e9f (where
 * BaseChildEvaluation::toReason() dropped the value of every negative Child credit, so it counted as 0).
 * Re-running it tears its own entities down first, so it is safe to repeat.
 *
 * The walkthrough for testers lives in docs/tests/MANUAL_TEST_NEGATIVE_CHILD_CREDIT.md.
 */
class NegativeChildCreditScenarioSeeder extends Seeder
{
    public const SPONSOR_NAME = 'Negative Child Credit Test Sponsor';
    public const SPONSOR_SHORTCODE = 'NEGV';
    public const CENTRE_NAME = 'Negative Child Credit Test Centre';
    public const CENTRE_PREFIX = 'NEGV';
    public const USER_NAME = 'ARC Negative Child Credit Tester';
    public const USER_EMAIL = 'arc+negv@neontribe.co.uk';
    public const USER_PASSWORD = 'store_pass';

    /** Carer names all start with this so the Store search finds the whole set. */
    public const CARER_PREFIX = 'NEGV';

    /** The sponsor modifier under test: each child at primary school takes this many vouchers off the total. */
    public const SCHOOL_AGE_DEDUCTION = -2;

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
            'can_tap' => true,
        ]);
        $sponsor->evaluations()->saveMany($this->sponsorOverrides());

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
     * The standard rule set plus the one modifier F16 needs: a negatively valued Child credit.
     * Standard rules stay as they are (under 1 = 6, between 1 and school = 4, pregnant = 4).
     *
     * @return Evaluation[]
     */
    public function sponsorOverrides(): array
    {
        return [
            // Deduct per child at primary school...
            new Evaluation([
                'name' => 'ChildIsPrimarySchoolAge',
                'value' => self::SCHOOL_AGE_DEDUCTION,
                'purpose' => 'credits',
                'entity' => 'App\Child',
            ]),
            // ...instead of the standard rule that only disqualifies them.
            new Evaluation([
                'name' => 'ChildIsPrimarySchoolAge',
                'value' => null,
                'purpose' => 'disqualifiers',
                'entity' => 'App\Child',
            ]),
        ];
    }

    /**
     * The scenarios. DOBs are the 1st of the month (as the Store always stores them) and are computed
     * relative to today. Ages are kept well clear of the school-start boundary (no 4-5 year olds) so the
     * set reads the same in every month and needs no ARC_SCHOOL_MONTH lever.
     *
     * @return array[]
     */
    public function scenarios(): array
    {
        return [
            [
                'key' => 'A',
                'title' => 'Control: baby only',
                'note' => '6-month-old only child. 6/wk on both branches; nothing negative involved.',
                'children' => [$this->childAged(0, 6)],
            ],
            [
                'key' => 'B',
                'title' => 'F16: toddler plus school-age child',
                'note' => '3-year-old (+4) and 8-year-old (-2). This branch 2/wk; 48331e9f drops the -2 and shows 4/wk.',
                'children' => [$this->childAged(3, 0), $this->childAged(8, 0)],
            ],
            [
                'key' => 'C',
                'title' => 'F16: two toddlers plus school-age child',
                'note' => '2-year-old and 3.5-year-old (+8) and a 9-year-old (-2). This branch 6/wk; 48331e9f shows 8/wk.',
                'children' => [$this->childAged(2, 0), $this->childAged(3, 6), $this->childAged(9, 0)],
            ],
            [
                'key' => 'D',
                'title' => 'F16: deduction can wipe the total',
                'note' => '3-year-old (+4) and two school-age children (-4). This branch 0/wk; 48331e9f shows 4/wk.',
                'children' => [$this->childAged(3, 0), $this->childAged(7, 0), $this->childAged(10, 0)],
            ],
            [
                'key' => 'E',
                'title' => 'Wording only: school-age only child',
                'note' => '9-year-old only child. 0/wk on both branches (the total is clamped at 0), but the printable credit line reads "-2 vouchers because one child is primary school age" here and "0 vouchers ..." on 48331e9f.',
                'children' => [$this->childAged(9, 0)],
            ],
            [
                'key' => 'F',
                'title' => 'Control: baby plus pregnancy',
                'note' => '6-month-old and a pregnancy due in three months. 10/wk on both branches.',
                'children' => [$this->childAged(0, 6), $this->childAged(0, -3)],
            ],
        ];
    }

    /**
     * A child born (or due) on the 1st of the month, the given age before today. Negative months are a
     * pregnancy due that far in the future.
     *
     * @param int $years
     * @param int $months
     * @return array{dob: Carbon}
     */
    private function childAged(int $years, int $months): array
    {
        return ['dob' => $this->today->copy()->startOfMonth()->subYears($years)->subMonths($months)];
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
     * One cheat-sheet row: what this branch says now, and what 48331e9f would say.
     *
     * @param array $scenario
     * @param Registration $registration
     * @return array
     */
    private function describe(array $scenario, Registration $registration): array
    {
        $registration = Registration::withFullFamily()->find($registration->id);
        $valuation = $registration->getValuation();

        $notices = array_map(fn($n) => $n['count'] . 'x ' . $n['reason'], $valuation->getNoticeReasons());

        $patched = $this->formatOutcome(
            $valuation->getEntitlement(),
            array_map(fn($c) => $this->creditLine($c['count'], $c['reason'], $c['reason_vouchers']), $valuation->getCreditReasons()),
            $notices
        );

        $old = $this->unpatchedValuation($valuation->flat('credits', true));
        $unpatched = $this->formatOutcome($old['entitlement'], $old['credits'], $notices);

        $children = array_map(function ($child) {
            /** @var Carbon $dob */
            $dob = $child['dob'];
            $age = $dob->isFuture()
                ? 'P'
                : $dob->diff($this->today)->format('%yy%mm');
            return $dob->format('M Y') . ' (' . $age . ')';
        }, $scenario['children']);

        return [
            'key' => $scenario['key'],
            'registration_id' => $registration->id,
            'rvid' => $registration->family->rvid,
            'carer' => self::carerName($scenario),
            'children' => implode("\n", $children),
            'entitlement' => $valuation->getEntitlement(),
            'unpatched_entitlement' => $old['entitlement'],
            'patched' => $patched,
            'unpatched' => $unpatched,
            'differs' => $patched === $unpatched ? '' : 'YES',
            'note' => $scenario['note'],
        ];
    }

    /**
     * @param int $count
     * @param string $reason
     * @param int $vouchers
     * @return string
     */
    private function creditLine(int $count, string $reason, int $vouchers): string
    {
        return sprintf('%dx %s (%d)', $count, $reason, $vouchers);
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
     | Model of the UNPATCHED BaseChildEvaluation (commit 48331e9f)
     |--------------------------------------------------------------------------
     | The only thing that changed is BaseChildEvaluation::toReason(): it used to keep the 'value' key
     | only when $this->value > 0, so a negative Child credit still appeared as a reason but summed as 0.
     | Everything else (which rules fire, the family credits, the max(0, ...) clamp) is taken live.
     */

    /**
     * Old whole-family outcome: the live eligible credits with every negative Child value dropped.
     *
     * @param array $credits raw eligible credits from Valuation::flat('credits', true)
     * @return array{entitlement:int, credits:string[]}
     */
    private function unpatchedValuation(array $credits): array
    {
        $reasons = [];
        foreach ($credits as $credit) {
            $value = $credit['value'] ?? 0;
            if (str_starts_with($credit['reason'], 'Child|') && $value < 0) {
                $value = 0;
            }
            $reasons[$credit['reason']] ??= ['count' => 0, 'value' => 0];
            $reasons[$credit['reason']]['count']++;
            $reasons[$credit['reason']]['value'] += $value;
        }

        $lines = [];
        $total = 0;
        foreach ($reasons as $reason => $credit) {
            $lines[] = $this->creditLine($credit['count'], explode('|', $reason)[1], $credit['value']);
            $total += $credit['value'];
        }

        return [
            'entitlement' => max(0, $total),
            'credits' => $lines,
        ];
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
            'Negative Child credit (F16) scenarios seeded for %s.',
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
            'Sponsor rule set: standard rules plus ChildIsPrimarySchoolAge as a credit of %d (the disqualifier is switched off).',
            self::SCHOOL_AGE_DEDUCTION
        ));
        $this->command->line('Columns: "This branch" is evaluated live; "48331e9f" is the same with every negative Child value dropped, as the old toReason() did.');
        $this->command->line('Credit lines read "<count>x <reason> (<vouchers>)". Nothing here depends on the date or on ARC_SCHOOL_MONTH.');
        $this->command->newLine();

        $this->command->table(
            ['Family', 'RVID', 'Children', 'This branch', '48331e9f (unpatched)', 'Differs?'],
            array_map(fn($row) => [
                $row['key'],
                $row['rvid'],
                $row['children'],
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

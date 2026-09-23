<?php

namespace Tests\Console\Commands;

use App\Evaluation;
use App\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Tests\CreatesApplication;

class AuditSponsorRulesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesApplication;

    /** @var Sponsor sponsor with no evaluations (pure defaults) */
    private Sponsor $sponsorA;

    /** @var Sponsor sponsor with a cancellation, a variant, a new rule and an orphan */
    private Sponsor $sponsorB;

    private Evaluation $cancelling;
    private Evaluation $variant;
    private Evaluation $newRule;
    private Evaluation $orphan;

    public function setUp(): void
    {
        parent::setUp();

        $this->sponsorA = factory(Sponsor::class)->create([
            'name' => 'Plain Sponsor',
            'shortcode' => 'PLAN',
            'programme' => 0,
        ]);

        $this->sponsorB = factory(Sponsor::class)->create([
            'name' => 'Custom Sponsor',
            'shortcode' => 'CUST',
            'programme' => 1,
        ]);

        $this->cancelling = Evaluation::create([
            'sponsor_id' => $this->sponsorB->id,
            'name' => 'FamilyIsPregnant',
            'entity' => 'App\Family',
            'purpose' => 'credits',
            'value' => null,
        ]);
        $this->variant = Evaluation::create([
            'sponsor_id' => $this->sponsorB->id,
            'name' => 'ChildIsUnderOne',
            'entity' => 'App\Child',
            'purpose' => 'credits',
            'value' => 5,
        ]);
        $this->newRule = Evaluation::create([
            'sponsor_id' => $this->sponsorB->id,
            'name' => 'HouseholdExists',
            'entity' => 'App\Family',
            'purpose' => 'credits',
            'value' => 10,
        ]);
        $this->orphan = Evaluation::create([
            'sponsor_id' => $this->sponsorB->id,
            'name' => 'NoSuchRule',
            'entity' => 'App\Family',
            'purpose' => 'credits',
            'value' => null,
        ]);
    }

    /**
     * Run the command and return [exitCode, output].
     *
     * @param array $params
     * @return array
     */
    private function runAudit(array $params = []): array
    {
        $exitCode = Artisan::call('arc:auditSponsorRules', $params);
        return [$exitCode, Artisan::output()];
    }

    /**
     * Collapse table whitespace so rows can be matched as "a | b | c".
     *
     * @param string $output
     * @return string
     */
    private function squash(string $output): string
    {
        return preg_replace('/\s+/', ' ', $output);
    }

    public function testAllSponsorsSummary(): void
    {
        [$exitCode, $output] = $this->runAudit();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Sponsor ' . $this->sponsorA->id . ' | Plain Sponsor | programme 0', $output);
        $this->assertStringContainsString('Sponsor ' . $this->sponsorB->id . ' | Custom Sponsor | programme 1', $output);
    }

    public function testSponsorById(): void
    {
        [$exitCode, $output] = $this->runAudit(['sponsor' => $this->sponsorA->id]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Plain Sponsor', $output);
        $this->assertStringNotContainsString('Custom Sponsor', $output);
    }

    public function testSponsorByShortcode(): void
    {
        [$exitCode, $output] = $this->runAudit(['sponsor' => 'CUST']);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Custom Sponsor', $output);
        $this->assertStringNotContainsString('Plain Sponsor', $output);
    }

    public function testUnknownSponsorReturns1(): void
    {
        [$exitCode, $output] = $this->runAudit(['sponsor' => 'NOPE']);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString("Can't find that Sponsor", $output);
    }

    public function testSummaryCancellation(): void
    {
        [, $output] = $this->runAudit(['sponsor' => $this->sponsorB->id]);
        $squashed = $this->squash($output);

        $this->assertStringContainsString(
            '| ' . $this->cancelling->id . ' | FamilyIsPregnant | credits | cancelled | db |',
            $squashed
        );
        // The cancelling row is not printed separately
        $this->assertEquals(1, substr_count($output, 'FamilyIsPregnant'));
    }

    public function testSummaryVariantDecoration(): void
    {
        [, $output] = $this->runAudit(['sponsor' => $this->sponsorB->id]);
        $squashed = $this->squash($output);

        $this->assertStringContainsString(
            '| ' . $this->variant->id . ' | ChildIsUnderOne | credits | 5* | db |',
            $squashed
        );
        $this->assertStringContainsString(
            '| ' . $this->newRule->id . ' | HouseholdExists | credits | 10 | db |',
            $squashed
        );
    }

    public function testSummaryDefaultsAreUndecorated(): void
    {
        [, $output] = $this->runAudit(['sponsor' => $this->sponsorA->id]);
        $squashed = $this->squash($output);

        $this->assertStringContainsString('| - | ChildIsUnderOne | credits | 6 | default |', $squashed);
        $this->assertStringContainsString('| - | FamilyIsPregnant | credits | 4 | default |', $squashed);
        $this->assertStringContainsString('| - | ChildIsPrimarySchoolAge | disqualifiers | 0 | default |', $squashed);
        $this->assertStringNotContainsString('*', $squashed);
        $this->assertStringNotContainsString('cancelled', $squashed);
    }

    public function testSummaryOrphanWarning(): void
    {
        [, $output] = $this->runAudit(['sponsor' => $this->sponsorB->id]);
        $squashed = $this->squash($output);

        $this->assertStringContainsString(
            '| ' . $this->orphan->id . ' | NoSuchRule | credits | cancelled (orphan) | db |',
            $squashed
        );
        $this->assertStringContainsString('is null but cancels no default rule', $output);
    }

    public function testEffectiveRuleCount(): void
    {
        [, $outputA] = $this->runAudit(['sponsor' => $this->sponsorA->id]);
        [, $outputB] = $this->runAudit(['sponsor' => $this->sponsorB->id]);

        $this->assertStringContainsString('| 6 effective rules', $outputA);
        // 6 defaults - 1 cancelled + 1 new
        $this->assertStringContainsString('| 6 effective rules', $outputB);
    }

    public function testGroupedByEntityFamilyFirst(): void
    {
        [, $output] = $this->runAudit(['sponsor' => $this->sponsorA->id]);

        $familyPos = strpos($output, 'App\Family');
        $childPos = strpos($output, 'App\Child');

        $this->assertNotFalse($familyPos);
        $this->assertNotFalse($childPos);
        $this->assertLessThan($childPos, $familyPos);
    }

    public function testDetailedShowsRawRows(): void
    {
        [$exitCode, $output] = $this->runAudit([
            'sponsor' => $this->sponsorB->id,
            '--detailed' => true,
        ]);
        $squashed = $this->squash($output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('| - | FamilyIsPregnant | credits | 4 | default |', $squashed);
        $this->assertStringContainsString(
            '| ' . $this->cancelling->id . ' | FamilyIsPregnant | credits | null | db |',
            $squashed
        );
        $this->assertStringContainsString('| - | ChildIsUnderOne | credits | 6 | default |', $squashed);
        $this->assertStringContainsString(
            '| ' . $this->variant->id . ' | ChildIsUnderOne | credits | 5 | db |',
            $squashed
        );
        $this->assertStringNotContainsString('*', $squashed);
        $this->assertStringNotContainsString('cancelled', $squashed);
    }

    public function testJsonOutput(): void
    {
        [$exitCode, $output] = $this->runAudit([
            'sponsor' => $this->sponsorB->id,
            '--json' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded, 'Output should be valid JSON');
        $this->assertCount(1, $decoded);

        $report = $decoded[0];
        $this->assertEquals($this->sponsorB->id, $report['id']);
        $this->assertEquals('Custom Sponsor', $report['name']);
        $this->assertEquals(1, $report['programme']);
        $this->assertEquals(6, $report['effective_rule_count']);
        $this->assertEquals('summary', $report['mode']);
        $this->assertArrayHasKey('App\Family', $report['rules']);
        $this->assertArrayHasKey('App\Child', $report['rules']);

        $family = collect($report['rules']['App\Family'])->keyBy('name');
        $this->assertEquals('cancelled', $family['FamilyIsPregnant']['value']);
        $this->assertEquals($this->cancelling->id, $family['FamilyIsPregnant']['id']);
        $this->assertEquals('10', $family['HouseholdExists']['value']);
        $this->assertEquals('cancelled (orphan)', $family['NoSuchRule']['value']);

        $child = collect($report['rules']['App\Child'])->keyBy('name');
        $this->assertEquals('5*', $child['ChildIsUnderOne']['value']);
        $this->assertEquals('db', $child['ChildIsUnderOne']['source']);
        $this->assertEquals('-', $child['ChildIsAlmostOne']['id']);
        $this->assertEquals('default', $child['ChildIsAlmostOne']['source']);
    }
}

<?php

namespace App\Console\Commands;

use App\Evaluation;
use App\Sponsor;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditSponsorRules extends Command
{
    /**
     * Copy of the hard-coded defaults in EvaluatorFactory::generateEvaluations().
     * Keep the two lists in sync manually.
     */
    private const DEFAULT_RULES = [
        [
            'name' => 'ChildIsUnderOne',
            'entity' => 'App\Child',
            'purpose' => 'credits',
            'value' => 6,
        ],
        [
            'name' => 'ChildIsBetweenOneAndPrimarySchoolAge',
            'entity' => 'App\Child',
            'purpose' => 'credits',
            'value' => 4,
        ],
        [
            'name' => 'ChildIsAlmostOne',
            'entity' => 'App\Child',
            'purpose' => 'notices',
            'value' => 0,
        ],
        [
            'name' => 'ChildIsAlmostPrimarySchoolAge',
            'entity' => 'App\Child',
            'purpose' => 'notices',
            'value' => 0,
        ],
        [
            'name' => 'ChildIsPrimarySchoolAge',
            'entity' => 'App\Child',
            'purpose' => 'disqualifiers',
            'value' => 0,
        ],
        [
            'name' => 'FamilyIsPregnant',
            'entity' => 'App\Family',
            'purpose' => 'credits',
            'value' => 4,
        ],
    ];

    private const ENTITY_ORDER = ['App\Family', 'App\Child'];

    private const CANCELLED = 'cancelled';
    private const CANCELLED_ORPHAN = 'cancelled (orphan)';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:auditSponsorRules
                                {sponsor? : Sponsor id (or shortcode); omit to audit all sponsors}
                                {--detailed : Raw iteration of all applicable rules (defaults + every DB row)}
                                {--json : Emit JSON instead of console tables}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audits the voucher evaluation rules that apply to one or all sponsors';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sponsors = $this->resolveSponsors();

        if ($sponsors->isEmpty()) {
            $this->error("Can't find that Sponsor.\n");
            return 1;
        }

        $detailed = (bool)$this->option('detailed');
        $report = [];

        foreach ($sponsors as $sponsor) {
            $rules = $detailed
                ? $this->detailedRules($sponsor)
                : $this->summaryRules($sponsor);

            $grouped = $this->groupByEntity($rules);
            $effectiveCount = count(array_filter($rules, function ($rule) {
                return !$rule['cancelled'];
            }));

            $report[] = [
                'id' => $sponsor->id,
                'name' => $sponsor->name,
                'programme' => (int)$sponsor->programme,
                'effective_rule_count' => $effectiveCount,
                'mode' => $detailed ? 'detailed' : 'summary',
                'rules' => $this->stripInternalKeys($grouped),
            ];

            if (!$this->option('json')) {
                $this->renderConsole($sponsor, $grouped, $effectiveCount);
            }
        }

        if ($this->option('json')) {
            $this->renderJson($report);
        }

        return 0;
    }

    /**
     * Fetch the requested sponsor(s) with their evaluations.
     *
     * @return Collection
     */
    private function resolveSponsors(): Collection
    {
        $query = Sponsor::with('evaluations')->orderBy('id');

        $identifier = $this->argument('sponsor');
        if ($identifier !== null) {
            $query->where(function ($q) use ($identifier) {
                $q->where('id', $identifier)
                    ->orWhere('shortcode', $identifier);
            });
        }

        return $query->get();
    }

    /**
     * Raw list: every default followed by every DB row, no merging.
     *
     * @param Sponsor $sponsor
     * @return array
     */
    private function detailedRules(Sponsor $sponsor): array
    {
        $rules = [];

        foreach (self::DEFAULT_RULES as $default) {
            $rules[] = $this->makeRow(
                '-',
                $default['entity'],
                $default['name'],
                $default['purpose'],
                (string)$default['value'],
                'default'
            );
        }

        /** @var Evaluation $evaluation */
        foreach ($sponsor->evaluations as $evaluation) {
            $rules[] = $this->makeRow(
                $evaluation->id,
                $evaluation->entity,
                $evaluation->name,
                $evaluation->purpose,
                $evaluation->value === null ? 'null' : (string)$evaluation->value,
                'db',
                $evaluation->value === null
            );
        }

        return $rules;
    }

    /**
     * Effective list: defaults overlaid with DB rows, cancellations and variants marked.
     *
     * @param Sponsor $sponsor
     * @return array
     */
    private function summaryRules(Sponsor $sponsor): array
    {
        $map = [];

        foreach (self::DEFAULT_RULES as $default) {
            $key = $this->ruleKey($default['purpose'], $default['name']);
            $map[$key] = $this->makeRow(
                '-',
                $default['entity'],
                $default['name'],
                $default['purpose'],
                (string)$default['value'],
                'default'
            );
        }

        /** @var Evaluation $evaluation */
        foreach ($sponsor->evaluations as $evaluation) {
            $key = $this->ruleKey($evaluation->purpose, $evaluation->name);
            $default = $map[$key] ?? null;

            if ($evaluation->value === null) {
                if ($default !== null) {
                    // Cancels a default; keep the default row, mark it cancelled.
                    $map[$key] = $this->makeRow(
                        $evaluation->id,
                        $default['entity'],
                        $default['name'],
                        $default['purpose'],
                        self::CANCELLED,
                        'db',
                        true
                    );
                } else {
                    // Nothing to cancel; stale row. Keep stdout pipe-safe in --json mode.
                    if (!$this->option('json')) {
                        $this->warn(sprintf(
                            'Sponsor %d: evaluation %d (%s / %s) is null but cancels no default rule',
                            $sponsor->id,
                            $evaluation->id,
                            $evaluation->purpose,
                            $evaluation->name
                        ));
                    }
                    $map[$key] = $this->makeRow(
                        $evaluation->id,
                        $evaluation->entity,
                        $evaluation->name,
                        $evaluation->purpose,
                        self::CANCELLED_ORPHAN,
                        'db',
                        true
                    );
                }
                continue;
            }

            $value = (string)$evaluation->value;
            if ($default !== null && (int)$default['value'] !== (int)$evaluation->value) {
                // Amends a default value.
                $value .= '*';
            }

            $map[$key] = $this->makeRow(
                $evaluation->id,
                $evaluation->entity,
                $evaluation->name,
                $evaluation->purpose,
                $value,
                'db'
            );
        }

        return array_values($map);
    }

    /**
     * @param string $purpose
     * @param string $name
     * @return string
     */
    private function ruleKey(string $purpose, string $name): string
    {
        return $purpose . ':' . $name;
    }

    /**
     * @param int|string $id
     * @param string $entity
     * @param string $name
     * @param string $purpose
     * @param string $value
     * @param string $source
     * @param bool $cancelled
     * @return array
     */
    private function makeRow($id, string $entity, string $name, string $purpose, string $value, string $source, bool $cancelled = false): array
    {
        return [
            'id' => $id,
            'entity' => $entity,
            'name' => $name,
            'purpose' => $purpose,
            'value' => $value,
            'source' => $source,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Group rows by entity, Family then Child then others alphabetically;
     * within an entity sort by purpose then name.
     *
     * @param array $rules
     * @return array
     */
    private function groupByEntity(array $rules): array
    {
        $grouped = [];
        foreach ($rules as $rule) {
            $grouped[$rule['entity']][] = $rule;
        }

        uksort($grouped, function ($a, $b) {
            $ia = array_search($a, self::ENTITY_ORDER, true);
            $ib = array_search($b, self::ENTITY_ORDER, true);
            $ia = $ia === false ? PHP_INT_MAX : $ia;
            $ib = $ib === false ? PHP_INT_MAX : $ib;
            return $ia === $ib ? strcmp($a, $b) : $ia <=> $ib;
        });

        foreach ($grouped as &$rows) {
            usort($rows, function ($a, $b) {
                return [$a['purpose'], $a['name']] <=> [$b['purpose'], $b['name']];
            });
        }
        unset($rows);

        return $grouped;
    }

    /**
     * Remove the internal 'cancelled' and 'entity' keys for JSON output.
     *
     * @param array $grouped
     * @return array
     */
    private function stripInternalKeys(array $grouped): array
    {
        foreach ($grouped as $entity => $rows) {
            $grouped[$entity] = array_map(function ($row) {
                unset($row['cancelled'], $row['entity']);
                return $row;
            }, $rows);
        }
        return $grouped;
    }

    /**
     * @param Sponsor $sponsor
     * @param array $grouped
     * @param int $effectiveCount
     */
    private function renderConsole(Sponsor $sponsor, array $grouped, int $effectiveCount): void
    {
        $this->info(sprintf(
            'Sponsor %d | %s | programme %d | %d effective rules',
            $sponsor->id,
            $sponsor->name,
            $sponsor->programme,
            $effectiveCount
        ));

        foreach ($grouped as $entity => $rows) {
            $this->line('');
            $this->line($entity);
            $this->table(
                ['id', 'name', 'purpose', 'value', 'source'],
                array_map(function ($row) {
                    return [
                        $row['id'],
                        $row['name'],
                        $row['purpose'],
                        $row['value'],
                        $row['source'],
                    ];
                }, $rows)
            );
        }
        $this->line('');
    }

    /**
     * @param array $report
     */
    private function renderJson(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }
}

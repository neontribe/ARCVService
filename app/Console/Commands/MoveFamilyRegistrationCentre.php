<?php

namespace App\Console\Commands;

use App\Centre;
use App\Family;
use App\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MoveFamilyRegistrationCentre extends Command
{
    protected $signature = 'arc:move-family-reg
        {family_id? : Single family ID to move}
        {centre_id? : to which centre}
        {--csv= : Path to CSV file containing an RVID and CENTRE column}
        {--dry-run : Show what would be deleted}
        {--force : Skip confirmation prompt}';

    protected $description = 'Permanently moves a family and related graph data, including voucher handouts';

    public function handle(): int
    {
        $familyId = $this->argument('family_id');
        $centreId = $this->argument('centre_id');
        $csvPath = $this->option('csv');
        $dryRun = (bool)$this->option('dry-run');
        $force = (bool)$this->option('force');

        if (
            ($csvPath && ($centreId || $familyId)) ||
            (!$csvPath && !($centreId && $familyId))) {
            $this->error('Provide either a family_id and centre_id OR --csv=path');
            return self::FAILURE;
        }

        $workList = collect();

        if ($familyId && $centreId) {
            $workList->push(["familyId" => (int)$familyId, "centreId" => (int)$centreId]);
        }

        if ($csvPath) {
            if (!file_exists($csvPath)) {
                $this->error("CSV file not found: {$csvPath}");
                return self::FAILURE;
            }

            $workList = $workList->merge(
                $this->extractFromCsv($csvPath)
            );
        }

        // can filter work on arrays?
        $workList = $workList->filter()->unique(
            function ($item) {
                return $item['familyId'] . '-' . $item['centreId'];
            }
        )->values();

        $this->info("Processing {$workList->count()} families");

        $failed = [];

        foreach ($workList as $item) {
            ['familyId' => $familyId, 'centreId' => $centreId] = $item;
            $this->line('');
            $this->line("==== FAMILY {$familyId} ====");

            $result = $this->moveSingleFamily($familyId, $centreId, $dryRun, $force);

            if ($result !== self::SUCCESS) {
                $failed[] = $familyId;
            }
        }

        $this->line('');
        $this->info('Batch complete.');

        if (!empty($failed)) {
            $this->warn('Failed family IDs:');
            $this->line(implode(', ', $failed));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function extractFromCsv(string $path): Collection
    {
        $pairs = collect();

        if (($handle = fopen($path, 'rb')) === false) {
            throw new RuntimeException("Cannot open CSV: {$path}");
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new RuntimeException('CSV has no rows.');
        }

        $rvidIndex = array_search('RVID', $header, true);
        $centreIndex = array_search('Centre', $header, true);

        if ($rvidIndex === false) {
            fclose($handle);
            throw new RuntimeException('CSV missing RVID header column.');
        }

        if ($centreIndex === false) {
            fclose($handle);
            throw new RuntimeException('CSV missing Centre header column.');
        }

        $centreMap = Centre::query()->pluck('id', 'name');

        while (($row = fgetcsv($handle)) !== false) {

            $rvid = trim((string)($row[$rvidIndex] ?? ''));
            $centreName = trim((string)($row[$centreIndex] ?? ''));

            $family = self::findByRvid($rvid);

            if (!$family) {
                $this->line("Invalid RVID: {$rvid}");
                continue;
            }

            $centreId = $centreMap[$centreName] ?? null;

            if (!$centreId) {
                $this->line("Invalid Centre: {$centreName}");
                continue;
            }

            $pairs->push(['familyId' => $family->id, 'centreId' => $centreId]);
        }

        fclose($handle);

        return $pairs;
    }

    public static function findByRvid(string $rvid): ?Family
    {
        $rvid = strtoupper(trim($rvid));

        if ($rvid === '') {
            return null;
        }

        // IMPORTANT: longest prefix first (prevents AB matching before AB1)
        $centres = Centre::query()
            ->select('id', 'prefix')
            ->orderByRaw('LENGTH(prefix) DESC')
            ->get()->all();

        foreach ($centres as $centre) {
            if (!str_starts_with($rvid, $centre->prefix)) {
                continue;
            }
            $sequencePart = substr($rvid, strlen($centre->prefix));

            if (!ctype_digit($sequencePart)) {
                continue;
            }

            $sequence = (int)$sequencePart;

            return Family::query()
                ->where('initial_centre_id', $centre->id)
                ->where('centre_sequence', $sequence)
                ->first();
        }

        return null;
    }

    private function moveSingleFamily(int $familyId, int $centreId, bool $dryRun, bool $force): int
    {
        $centre = Centre::find($centreId);
        if (!$centre) {
            $this->error("Centre {$centreId} not found.");
            return self::FAILURE;
        }

        $family = Family::withPrimaryCarer()->whereKey($familyId)->lockForUpdate()->first();
        if (!$family) {
            $this->error("Family {$familyId} not found.");
            return self::FAILURE;
        }

        try {
            return DB::transaction(callback: function () use ($family, $centre, $dryRun, $force) {

                $registrations = Registration::where('family_id', $family->id)->with('centre')->get();
                $centreNames = $registrations->pluck('centre.name')->all();
                $centreNames = implode(", ", array_unique(array_sort($centreNames)));

                $this->info("Family: {$family->id}");
                $this->info("Primary Carer: {$family->pri_carer}");
                $this->line("Registrations: {$registrations->count()}");
                $this->line("In Centres: {$centreNames}");
                $this->line("Move to Centre: {$centre->id} ({$centre->name})");

                if ($dryRun) {
                    $this->warn('Dry run complete — nothing deleted.');
                    return self::SUCCESS;
                }

                if (
                    !$force && !$this->confirm(
                        "This will permanently move family {$family->id} and related data to {$centre->name}. Continue?"
                    )
                ) {
                    $this->warn('Skipped');
                    return self::FAILURE;
                }

                if ($registrations->isNotEmpty()) {
                    $this->moveRegistrations($registrations, $centre);
                    $family->lockToCentre($centre, true);
                }

                $this->info('Family Registration permanently moved.');
                return self::SUCCESS;
            }, attempts: 3);
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function moveRegistrations(Collection $registrations, $centre): void
    {
        $expected = $registrations->count();
        $actioned = Registration::query()
            ->whereIn('id', $registrations->modelKeys())
            ->update([
                'centre_id' => $centre->id,
            ]);

        if ($actioned !== $expected) {
            throw new RuntimeException(
                "Registration move mismatch. Expected {$expected}, moved {$actioned}."
            );
        }
    }
}

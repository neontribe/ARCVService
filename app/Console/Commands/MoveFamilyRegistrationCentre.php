<?php

namespace App\Console\Commands;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Child;
use App\Family;
use App\Registration;
use App\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MoveFamilyRegistrationCentre extends Command
{
    protected $signature = 'arc:move-family-reg
        {family_id? : Single family ID to move}
        {center_id? : to which centre}
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
            $workList->push(["familyId" => (int)$familyId,"centreId" => (int)$centreId]);
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
            [$id, $centreId] = $item;
            $this->line('');
            $this->line("==== FAMILY {$id} ====");

            $result = $this->moveSingleFamily($id, $centreId, $dryRun, $force);

            if ($result !== self::SUCCESS) {
                $failed[] = $id;
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
        $ids = collect();

        if (($handle = fopen($path, 'rb')) === false) {
            throw new RuntimeException("Cannot open CSV: {$path}");
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new RuntimeException('CSV has no rows.');
        }

        $rvid = array_search('RVID', $header, true);

        if ($rvid === false) {
            fclose($handle);
            throw new RuntimeException('CSV missing RVID header column.');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $family = self::findByRvid($row[$rvid]);
            if ($family !== null) {
                $ids->push($family->id);
            } else {
                $this->line("Invalid rvid: {$row[$rvid]}");
            }
        }

        fclose($handle);
        return $ids;
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

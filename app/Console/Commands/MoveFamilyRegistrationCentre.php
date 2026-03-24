<?php

namespace App\Console\Commands;

use App\Centre;
use App\Family;
use App\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MoveFamilyRegistrationCentre extends Command
{
    public array $summary = [];
    protected $signature = 'arc:move-family-reg
        {family_id? : Single family ID to move}
        {centre_id? : to which centre}
        {--csv= : Path to CSV file containing `RVID` and `Centre` columns}
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
            (!$csvPath && !($centreId && $familyId))
        ) {
            $this->error('Provide either a family_id and centre_id OR --csv=path');
            return self::FAILURE;
        }

        $workList = collect();

        if ($familyId && $centreId) {
            $workList->push(["familyId" => (int)$familyId, "centreId" => (int)$centreId]);
        }

        if ($csvPath) {
            if (!Storage::exists($csvPath)) {
                $this->error("CSV file not found: $csvPath");
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
            $this->line("==== FAMILY $familyId ====");

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

        $path = "movesDone.csv";
        if (count($this->summary) > 0) {
            $this->writeCsv($path, $this->summary);
            $this->line("Wrote changes to $path");
        }

        return self::SUCCESS;
    }

    private function extractFromCsv(string $path): Collection
    {
        $pairs = collect();

        $handle = Storage::readStream($path);

        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV: $path");
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

            $family = Family::findByRvid($rvid);

            if (!$family) {
                $this->line("Invalid RVID: $rvid");
                continue;
            }

            $centreId = $centreMap[$centreName] ?? null;

            if (!$centreId) {
                $this->line("Invalid Centre: $centreName");
                continue;
            }

            $pairs->push([
                'familyId' => $family->id,
                'centreId' => $centreId,
            ]);
        }

        fclose($handle);

        return $pairs;
    }

    private function moveSingleFamily(int $familyId, int $centreId, bool $dryRun, bool $force): int
    {
        $centre = Centre::find($centreId);
        if (!$centre) {
            $this->error("Centre $centreId not found.");
            return self::FAILURE;
        }

        $family = Family::withPrimaryCarer()->whereKey($familyId)->lockForUpdate()->first();
        if (!$family) {
            $this->error("Family $familyId not found.");
            return self::FAILURE;
        }

        try {
            return DB::transaction(callback: function () use ($family, $centre, $dryRun, $force) {

                $oldRVID = $family->rvid;
                $oldName = $family->pri_carer;

                $registrations = Registration::where('family_id', $family->id)->with('centre')->get();
                $centreNames = $registrations->pluck('centre.name')->all();
                $centreNames = implode(", ", array_unique(array_sort($centreNames)));

                $this->info("Family: $family->id ($family->rvid)");
                $this->info("Primary Carer: $family->pri_carer");
                $this->line("Registrations: {$registrations->count()}");
                $this->line("In Centres: $centreNames");
                $this->line("Move to Centre: $centre->id ($centre->name)");

                if ($dryRun) {
                    $this->warn('Dry run complete — nothing deleted.');
                    return self::SUCCESS;
                }

                if (
                    !$force && !$this->confirm(
                        "This will permanently move family $family->id and related data to $centre->name. Continue?"
                    )
                ) {
                    $this->warn('Skipped');
                    return self::FAILURE;
                }

                if ($registrations->isNotEmpty()) {
                    $this->moveRegistrations($registrations, $centre);
                    $family->lockToCentre($centre, true);
                    $family->save();
                }

                // refresh model.
                $family->refresh();
                $newRVID = $family->rvid;
                $this->summary[] = [$oldRVID, $oldName, $newRVID];

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
                "Registration move mismatch. Expected $expected, moved $actioned."
            );
        }
    }

    private function writeCsv(string $path, iterable $rows): void
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Cannot open temp stream.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);

        Storage::put($path, stream_get_contents($stream));

        fclose($stream);
    }
}

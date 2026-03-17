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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class PurgeFamilyGraph extends Command
{
    protected $signature = 'arc:purge-family
        {family_id? : Single family ID to purge}
        {--csv= : Path to CSV file containing an `RVID` column}
        {--dry-run : Show what would be deleted}
        {--force : Skip confirmation prompt}';

    protected $description = 'Permanently deletes a family and related graph data, including voucher handouts';

    public function handle(): int
    {
        $familyId = $this->argument('family_id');
        $csvPath = $this->option('csv');
        $dryRun = (bool)$this->option('dry-run');
        $force = (bool)$this->option('force');

        if (!$familyId && !$csvPath) {
            $this->error('Provide either a family_id OR --csv=path');
            return self::FAILURE;
        }

        $familyIds = collect();

        if ($familyId) {
            $familyIds->push((int)$familyId);
        }

        if ($csvPath) {
            if (!file_exists($csvPath)) {
                $this->error("CSV file not found: {$csvPath}");
                return self::FAILURE;
            }

            $familyIds = $familyIds->merge(
                $this->extractFamilyIdsFromCsv($csvPath)
            );
        }

        $familyIds = $familyIds->filter()->unique()->values();

        $this->info("Processing {$familyIds->count()} families");

        $failed = [];

        foreach ($familyIds as $id) {
            $this->line('');
            $this->line("==== FAMILY {$id} ====");

            $result = $this->purgeSingleFamily($id, $dryRun, $force);

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

    private function extractFamilyIdsFromCsv(string $path): Collection
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

    private function purgeSingleFamily(int $familyId, bool $dryRun, bool $force): int
    {
        try {
            return DB::transaction(callback: function () use ($familyId, $dryRun, $force) {

                $family = Family::withPrimaryCarer()->whereKey($familyId)->lockForUpdate()->first();

                if (!$family) {
                    $this->error("Family {$familyId} not found.");
                    return self::FAILURE;
                }

                $registrationIds = Registration::where('family_id', $familyId)->pluck('id');

                $bundleIds = $registrationIds->isEmpty()
                    ? collect()
                    : Bundle::whereIn('registration_id', $registrationIds)->pluck('id');

                $voucherCount = $bundleIds->isEmpty()
                    ? 0
                    : Voucher::whereIn('bundle_id', $bundleIds)->count();

                $childrenCount = Child::where('family_id', $familyId)->count();
                $carersCount = Carer::where('family_id', $familyId)->withTrashed()->count();

                $this->info("Family: {$familyId}");
                $this->info("Primary Carer: {$family->pri_carer}");
                $this->line("Registrations: {$registrationIds->count()}");
                $this->line("Bundles: {$bundleIds->count()}");
                $this->line("Vouchers to detach: {$voucherCount}");
                $this->line("Children: {$childrenCount}");
                $this->line("Carers (including trashed): {$carersCount}");

                if ($dryRun) {
                    $this->warn('Dry run complete — nothing deleted.');
                    return self::SUCCESS;
                }

                if (
                    !$force && !$this->confirm(
                        "This will permanently purge family {$familyId} and related data. Continue?"
                    )
                ) {
                    $this->warn('Skipped');
                    return self::FAILURE;
                }

                if ($bundleIds->isNotEmpty()) {
                    $this->detachVouchersFromBundles($bundleIds);
                    $this->deleteBundles($bundleIds);
                }

                if ($registrationIds->isNotEmpty()) {
                    $this->deleteRegistrations($registrationIds);
                }

                $deletedChildren = $this->deleteChildren($familyId);
                if ($deletedChildren !== $childrenCount) {
                    throw new RuntimeException(
                        "Child delete mismatch. Expected {$childrenCount}, deleted {$deletedChildren}."
                    );
                }

                $deletedCarers = $this->deleteCarers($familyId);
                if ($deletedCarers !== $carersCount) {
                    throw new RuntimeException(
                        "Carer delete mismatch. Expected {$carersCount}, deleted {$deletedCarers}."
                    );
                }

                $deletedFamily = $this->deleteFamily($family);
                if ($deletedFamily !== 1) {
                    throw new RuntimeException("Family delete failed for family {$familyId}.");
                }

                $this->info('Family graph permanently deleted.');
                return self::SUCCESS;
            }, attempts: 3);
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function detachVouchersFromBundles(Collection $bundleIds): void
    {
        foreach ($bundleIds->chunk(1000) as $chunk) {
            Voucher::whereIn('bundle_id', $chunk->all())->update(['bundle_id' => null]);
        }
    }

    private function deleteBundles(Collection $bundleIds): void
    {
        $expected = $bundleIds->count();
        $deleted = 0;

        foreach ($bundleIds->chunk(1000) as $chunk) {
            $affected = Bundle::whereIn('id', $chunk->all())->delete();
            $deleted += $affected;
        }

        if ($deleted !== $expected) {
            throw new RuntimeException(
                "Bundle delete mismatch. Expected {$expected}, deleted {$deleted}."
            );
        }
    }

    private function deleteRegistrations(Collection $registrationIds): void
    {
        $expected = $registrationIds->count();
        $deleted = 0;

        foreach ($registrationIds->chunk(1000) as $chunk) {
            $affected = Registration::whereIn('id', $chunk->all())->delete();
            $deleted += $affected;
        }

        if ($deleted !== $expected) {
            throw new RuntimeException(
                "Registration delete mismatch. Expected {$expected}, deleted {$deleted}."
            );
        }
    }

    private function deleteChildren(int $familyId): int
    {
        return (int)Child::where('family_id', $familyId)->delete();
    }

    private function deleteCarers(int $familyId): int
    {
        // carers are softDelete-able
        return (int)Carer::where('family_id', $familyId)->withTrashed()->forceDelete();
    }

    private function deleteFamily($family): int
    {
        return (int)$family->delete();
    }
}

<?php

namespace App\Console\Commands;

use App\Bundle;
use App\Carer;
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
                            {family_id : The family ID to purge}
                            {--dry-run : Show what would be deleted}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Permanently deletes a family and related graph data, including voucher handouts';

    public function handle(): int
    {
        $familyId = (int) $this->argument('family_id');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        try {
            return DB::transaction(callback: function () use ($familyId, $dryRun, $force) {

                $family = Family::whereKey($familyId)->lockForUpdate()->first();

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
                $this->line("Registrations: {$registrationIds->count()}");
                $this->line("Bundles: {$bundleIds->count()}");
                $this->line("Vouchers to detach: {$voucherCount}");
                $this->line("Children: {$childrenCount}");
                $this->line("Carers (including trashed): {$carersCount}");

                if ($dryRun) {
                    $this->warn('Dry run complete — nothing deleted.');
                    return self::SUCCESS;
                }

                if (!$force) {
                    $confirmed = $this->confirm(
                        "This will permanently purge family {$familyId} and related data. Continue?"
                    );

                    if (!$confirmed) {
                        $this->warn('Aborted.');
                        return self::FAILURE;
                    }
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

    private function deleteChildren(int $familyId): int
    {
        return (int) Child::where('family_id', $familyId)->delete();
    }

    private function deleteCarers(int $familyId): int
    {
        // carers are softDelete-able
        return (int) Carer::where('family_id', $familyId)->withTrashed()->forceDelete();
    }

    private function deleteFamily($family): int
    {
        return (int) $family->delete();
    }
}

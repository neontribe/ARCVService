<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Family;
use App\Registration;
use App\Bundle;
use App\Carer;
use App\Child;
use App\Voucher;

class PurgeFamilyGraph extends Command
{
    protected $signature = 'arc:purge-family 
                            {family_id : The family ID to purge}
                            {--dry-run : Show what would be deleted}';

    protected $description = 'Permanently deletes a family and all related graph data from a centre, including voucher handouts';

    public function handle(): int
    {
        $familyId = (int) $this->argument('family_id');
        $dryRun = $this->option('dry-run');

        $family = Family::find($familyId);

        if (!$family) {
            $this->error("Family {$familyId} not found.");
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $registrations = Registration::where('family_id', $familyId)->pluck('id');

            $bundles = Bundle::whereIn('registration_id', $registrations)->pluck('id');

            $childrenCount = Child::where('family_id', $familyId)->count();
            $carersCount = Carer::withTrashed()->where('family_id', $familyId)->count();

            $this->info("Family: {$familyId}");
            $this->line("Registrations: " . $registrations->count());
            $this->line("Bundles: " . $bundles->count());
            $this->line("Children: " . $childrenCount);
            $this->line("Carers: " . $carersCount);

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete — nothing deleted.');
                return self::SUCCESS;
            }

            // 1️ Null vouchers.bundle_id
            Voucher::whereIn('bundle_id', $bundles)->update([
                'bundle_id' => null
            ]);

            // 2️ Delete bundles
            Bundle::whereIn('id', $bundles)->delete();

            // 3️ Delete registrations
            Registration::whereIn('id', $registrations)->delete();

            // 4️ Delete children
            Child::where('family_id', $familyId)->delete();

            // 5️ Force delete carers (soft delete table)
            Carer::withTrashed()
                ->where('family_id', $familyId)
                ->forceDelete();

            // 6️ Delete family
            $family->delete();

            // commit the work
            DB::commit();

            $this->info('Family graph permanently deleted.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveVouchers extends Command
{
    protected $signature = 'vouchers:archive';
    protected $description = 'Efficiently archive vouchers and their histories into separate tables in batches';

    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->info('Starting the archive process.');

            // Step 1: Create the archive tables if they do not exist
            $this->createArchiveTables();

            // Step 2: Process vouchers in batches
            $this->info('Processing vouchers in batches...');
            $batchSize = 5000; // Adjust batch size as needed for performance
            DB::table('vouchers')
                ->where('status', 'archivable') // Example condition
                ->orderBy('id')
                ->chunk($batchSize, function ($vouchers) {
                    foreach ($vouchers as $voucher) {
                        // Insert voucher into archive table
                        DB::table('archived_vouchers')->insert((array)$voucher);

                        // Fetch and insert related states
                        $voucherStates = DB::table('voucher_states')
                            ->where('voucher_id', $voucher->id)
                            ->get();

                        foreach ($voucherStates as $state) {
                            DB::table('archived_voucher_states')->insert((array)$state);
                        }

                        // Delete voucher and its states from original tables
                        DB::table('voucher_states')->where('voucher_id', $voucher->id)->delete();
                        DB::table('vouchers')->where('id', $voucher->id)->delete();
                    }
                });

            // Step 6: Optimize the original tables
            $this->info('Optimizing original tables...');
            DB::statement('OPTIMIZE TABLE vouchers');
            DB::statement('OPTIMIZE TABLE voucher_states');

            DB::commit();
            $this->info('Archiving process completed successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    private function createArchiveTables()
    {
        // Use existing tables as templates
        if (!Schema::hasTable('archived_vouchers')) {
            DB::statement('CREATE TABLE archived_vouchers LIKE vouchers');
            $this->info('Created table: archived_vouchers using vouchers as template.');
        } else {
            $this->info('Table archived_vouchers already exists.');
        }

        if (!Schema::hasTable('archived_voucher_states')) {
            DB::statement('CREATE TABLE archived_voucher_states LIKE voucher_states');
            $this->info('Created table: archived_voucher_states using voucher_states as template.');
        } else {
            $this->info('Table archived_voucher_states already exists.');
        }

        $this->info('Disabling indexes on archive tables...');
        DB::statement('ALTER TABLE archived_vouchers DISABLE KEYS');
        DB::statement('ALTER TABLE archived_voucher_states DISABLE KEYS');
    }
}

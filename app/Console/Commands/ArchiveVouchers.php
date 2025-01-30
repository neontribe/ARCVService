<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Log;

class ArchiveVouchers extends Command
{
    protected $date = "2023-09-01";
    protected $signature = 'arc:archiveVouchers';
    protected $description = 'archive vouchers and their histories';

    public function handle(): void
    {
        $this->info('Starting the archive process.');

        $this->info('Selecting vouchers for archiving and storing IDs in a table...');
        $this->createTemporaryTable();
        $this->populateTemporaryTable();

        $this->info('Archiving vouchers and their histories...');
        DB::statement("DROP TABLE IF EXISTS archived_voucher_states");
        DB::statement("DROP TABLE IF EXISTS archived_vouchers");
        DB::statement("
                CREATE TABLE archived_vouchers
                SELECT * FROM vouchers
                WHERE id IN (SELECT voucher_id FROM archive_voucher_ids)
            ");
        DB::statement("
            CREATE TABLE archived_voucher_states
            SELECT voucher_states.* FROM voucher_states
                JOIN archive_voucher_ids ON voucher_states.voucher_id = archive_voucher_ids.voucher_id
        ");

        $this->info('Deleting archived data from original tables...');
        $this->dropIndexes();
        DB::statement("
            DELETE vouchers, voucher_states FROM vouchers JOIN voucher_states
                ON voucher_states.voucher_id = vouchers.id
                WHERE vouchers.updated_at < '$this->date'
                AND vouchers.currentstate IN ('reimbursed', 'retired', 'voided', 'expired')
            ");
        $this->makeIndexes();

        $this->info('Optimizing original tables...');
        DB::statement('OPTIMIZE TABLE vouchers');
        DB::statement('OPTIMIZE TABLE voucher_states');

        $this->info('Archiving process completed.');
    }

    private function createTemporaryTable(): void
    {
        // Create a temporary table to hold voucher IDs for processing
        DB::statement("DROP TABLE IF EXISTS archive_voucher_ids");
        DB::statement("CREATE TABLE archive_voucher_ids (voucher_id BIGINT PRIMARY KEY)");
        $this->info('Temporary table archive_voucher_ids created.');
    }

    private function populateTemporaryTable(): void
    {
        $batchSize = 20000; // Adjust based on memory and system performance

        // Get the selection criteria
        $query = $this->getSelectionCriteria();

        // Get the total number of vouchers to archive for progress tracking
        $totalVouchers = $query->count();
        $this->info("Total vouchers to archive: $totalVouchers");

        if ($totalVouchers === 0) {
            $this->info('No vouchers found for archiving.');
            return;
        }

        // Initialize a counter to track progress
        $processedVouchers = 0;

        // Populate the temporary table in batches
        $query->orderBy('id')->chunk($batchSize, function ($vouchers) use (&$processedVouchers, $totalVouchers) {
            $ids = $vouchers->pluck('id')->toArray();
            DB::table('archive_voucher_ids')->insert(
                array_map(fn($id) => ['voucher_id' => $id], $ids)
            );

            // Update the progress counter
            $processedVouchers += count($vouchers);

            // Report progress
            $progressPercentage = round(($processedVouchers / $totalVouchers) * 100, 2);
            $this->info("Progress: $processedVouchers/$totalVouchers vouchers found ($progressPercentage%)");
        });

        $this->info('Table populated successfully.');
    }

    private function getSelectionCriteria(): Builder
    {
        // Define and return the query for selecting vouchers to archive
        return DB::table('vouchers')
            ->whereIn('currentstate', ['reimbursed', 'retired', 'voided', 'expired'])
            ->where('updated_at', '<', $this->date);
    }

    private function dropIndexes(): void
    {
        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        $dropIndexStatements = [
            "DROP INDEX voucher_states_created_at_index ON homestead.voucher_states",
            "DROP INDEX voucher_states_to_voucher_id_index ON homestead.voucher_states",
            "DROP INDEX voucher_states_voucher_id_index ON homestead.voucher_states",
            "DROP INDEX vouchers_code_index ON homestead.vouchers",
            "DROP INDEX vouchers_currentstate_index ON homestead.vouchers",
        ];

        foreach ($dropIndexStatements as $statement) {
            try {
                DB::statement($statement);
            } catch (\Exception $e) {
                Log::info("Could not drop index: " . $e->getMessage());
            }
        }
    }

    private function makeIndexes(): void
    {
        $createIndexStatements = [
            "CREATE INDEX voucher_states_created_at_index ON homestead.voucher_states (created_at)",
            "CREATE INDEX voucher_states_to_voucher_id_index ON homestead.voucher_states (`to`, voucher_id)",
            "CREATE INDEX voucher_states_voucher_id_index ON homestead.voucher_states (voucher_id)",
            "CREATE INDEX vouchers_code_index ON homestead.vouchers (code(10))",
            "CREATE INDEX vouchers_currentstate_index ON homestead.vouchers (currentstate)",
        ];

        foreach ($createIndexStatements as $statement) {
            try {
                DB::statement($statement);
            } catch (\Exception $e) {
                Log::info("Index creation failed: " . $e->getMessage());
            }
        }

        DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}

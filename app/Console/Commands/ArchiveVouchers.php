<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveVouchers extends Command
{
    protected array $tableData = [];

    protected string $date = "2023-09-01";
    protected $signature = 'arc:archiveVouchers';
    protected $description = 'archive vouchers and their histories';

    public function handle(): void
    {
        $this->tableData['vouchers'] = [
            'keys' => Schema::getForeignKeys('vouchers'),
            'indexes' => Schema::getIndexes('vouchers'),
        ];

        $this->tableData['voucher_states'] = [
            'keys' => Schema::getForeignKeys('voucher_states'),
            'indexes' => Schema::getIndexes('voucher_states'),
        ];

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

        $this->info('Removing indexes');
        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        $this->dropIndexes('voucher_states');
        $this->dropIndexes('vouchers');

        $this->info('Deleting archived data from original tables...');
        DB::statement("
            DELETE vouchers, voucher_states FROM vouchers JOIN voucher_states
                ON voucher_states.voucher_id = vouchers.id
                WHERE vouchers.updated_at < '$this->date'
                AND vouchers.currentstate IN ('reimbursed', 'retired', 'voided', 'expired')
            ");

        $this->info('Rebuilding indexes');
        $this->makeIndexes('vouchers');
        $this->makeIndexes('voucher_states');
        DB::statement("SET FOREIGN_KEY_CHECKS=1");

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
            DB::table('archive_voucher_ids')->insert(array_map(static fn($id) => ['voucher_id' => $id], $ids));

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
        return DB::table('vouchers')->whereIn('currentstate',
            ['reimbursed', 'retired', 'voided', 'expired'])->where('updated_at', '<', $this->date);
    }

    private function dropIndexes($tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $foreignKeys = $this->tableData[$tableName]['keys'];
            foreach ($foreignKeys as $fk) {
                $table->dropForeign($fk['name']);
            }

            $indexes = $this->tableData[$tableName]['indexes'];
            foreach ($indexes as $index) {
                if (!$index['primary']) {
                    $table->dropIndex($index['name']);
                }
            }
        });
    }

    private function makeIndexes($tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            // Re-add foreign keys
            $foreignKeys = $this->tableData[$tableName]['keys'];
            $keysFound = Arr::pluck(Schema::getForeignKeys($tableName), 'name');
            foreach ($foreignKeys as $fk) {
                if (!array_key_exists($fk['name'], $keysFound)) {
                    $table->foreign($fk['columns'],
                        $fk['name'])->references($fk['foreign_columns'])->on($fk['foreign_table']);
                }
            }

            $indexes = $this->tableData[$tableName]["indexes"];
            $indexesFound = Arr::pluck(Schema::getIndexes($tableName), 'name');

            // Re-add indexes
            foreach ($indexes as $index) {
                if (!array_key_exists($index['name'], $indexesFound) && !$index['primary']) {
                    $table->index($index['columns'], $index['name']);
                }
            }
        });
    }
}

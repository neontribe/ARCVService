<?php

namespace App\Console\Commands;

use App\Services\TextFormatter;
use App\Voucher;
use App\VoucherState;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MvlExport extends Command
{
    const DISK = "local"; // 'local' : 'enc';

    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:mvl:export
                            {--from= : start date (dd/mm/yyyy), will default to all vouchers}
                            {--to= : end date (dd/mm/yyyy), will default to September 2023}
                            {--chunk-size= : how many records to process each chunk}
                            ';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Exports a range of voucher ids/states and stores the MVL report under in /storage';

    /**
     * The date to start collecting vouchers from
     * @var Carbon $startDate
     */
    private Carbon $startDate;

    /**
     * The date that we care about for last year's data.
     * @var Carbon $endDate
     */
    private Carbon $endDate;

    /**
     * How many records to include pre export file
     *
     * @var int $chunkSize
     */
    private int $chunkSize = 100000;

    /**
     * Used to track execution time
     * @var float
     */
    private float $time;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->initSettings();

        $this->info(
            sprintf(
                "Starting voucher export from %s to %s in chunks of %d.",
                $this->startDate->format("Y/m/d"),
                $this->endDate->format("Y/m/d"),
                $this->chunkSize,
            )
        );

        $query = Voucher::where("currentstate", "=", "reimbursed")
            ->whereBetween('updated_at', [$this->startDate, $this->endDate])
            ->orderBy("updated_at");

        $this->info("Counting rows");
        $this->time = microtime(true);
        $count = $query->count();
        $this->info(sprintf(
            "Got %d vouchers in %s",
            $count,
            TextFormatter::secondsToTime(microtime(true) - $this->time)
        ));

        $today = Carbon::now()->format("Y-m-d");

        $outputDir = sprintf("%s/mvl/export/$today", Storage::path(self::DISK));
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info(sprintf(
            "Starting memory: %s",
            TextFormatter::formatBytes(memory_get_usage())
        ));
        $offset = 0;
        while ($offset < $count) {
            $this->time = microtime(true);
            $filename = sprintf(
                "%s/vouchers.%s-to-%s.%04d.arcx",
                $outputDir,
                $this->startDate->format("Ymd"),
                $this->endDate->format("Ymd"),
                floor(($offset + 1) / $this->chunkSize)
            );

            $voucherIds = $query->offset($offset)
                ->limit($this->chunkSize)
                ->pluck('id')
                ->toArray();

            file_put_contents($filename, join("\n", $voucherIds));
            $this->info(sprintf(
                "Wrote %d voucher ids to %s in %s, Mem: %s",
                count($voucherIds),
                $filename,
                TextFormatter::secondsToTime(microtime(true) - $this->time),
                TextFormatter::formatBytes(memory_get_usage())
            ));

            $offset += $this->chunkSize;
        }
    }

    /**
     * @return void
     */
    public function initSettings(): void
    {
        $from = $this->option('from');
        if ($from) {
            try {
                $this->startDate = Carbon::createFromFormat('d/m/Y', $from, 'UTC');
            } catch (InvalidFormatException $exception) {
                // Not a date
                $this->error("From date was not a valid date: " . $from);
                exit(1);
            }
        } else {
            // We were going to start this financial year
            // $this->startDate = $this->getStartOfThisFinancialYear()->format('d/m/Y');
            $this->startDate = Carbon::createFromFormat('d/m/Y', "01/01/1970", 'UTC');
        }

        $to = $this->option('to');
        if ($to) {
            try {
                $this->endDate = Carbon::createFromFormat('d/m/Y', $to, 'UTC');
            } catch (InvalidFormatException $exception) {
                // Not a date
                $this->error("To date was not a valid date: " . $to);
                exit(1);
            }
        } else {
            // We were going to use till now
            // $this->endDate = Carbon::now()->format('d/m/Y');
            $this->endDate = Carbon::createFromFormat('d/m/Y', "31/08/2023", 'UTC');
        }

        $chunkSize = $this->option("chunk-size");
        if ($chunkSize) {
            $this->chunkSize = intval($chunkSize);
            if ($this->chunkSize === 0) {
                $this->error("Chunk size does not seem to be a valid int: " . $chunkSize);
            }
        }

    }
}
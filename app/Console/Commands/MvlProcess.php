<?php

namespace App\Console\Commands;

use App\Services\TextFormatter;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MvlProcess extends Command
{
    const TICK_SIZE=1000;

    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:mvl:process {file : CSV file of voucher ids to process}';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Processes a single exported MVL file';

    /**
     * The sheet headers.
     *
     * @var array $headers
     */
    private array $headers = [
        'Voucher Number',
        'Voucher Area',
        'Date Distributed',
        'Distributed to Centre',
        'Distributed to Area',
        'Waiting for collection',
        'Date Issued',
        'RVID',
        'Main Carer',
        'Disbursing Centre',
        'Date Trader Recorded Voucher',
        'Retailer Name',
        'Retail Outlet',
        'Trader\'s Area',
        'Date Received for Reimbursement',
        'Reimbursed Date',
        'Void Voucher Date',
        'Void Reason',
        'Date file was Downloaded',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $in_file = $this->argument("file");
        $this->info(sprintf("Reading ids from %s", $in_file));
        if (!file_exists($in_file)) {
            $this->error(sprintf("File not found: %s", $in_file));
        }

        if (!file_exists(dirname($in_file) . "/_headers.csv")) {
            $fh = fopen(dirname($in_file) . "/_headers.csv", "w");
            fputcsv($fh, $this->headers);
            fclose($fh);
        }

        $out_file = dirname($in_file) . "/" . basename($in_file, ".txt") . ".csv";
        $this->info(sprintf("Writing ids to %s", $out_file));

        $fh_out = fopen($out_file, 'w');
        $count = 0;
        $startTime = microtime(true);
        $time = microtime(true);
        $lines = explode("\n", file_get_contents($in_file));

        $sharedData = [ null, null, $now = Carbon::now()->format("Y/m/d")];
        foreach ($lines as $id) {
            $v = Voucher::find($id);
            if ($v) {
                fputcsv($fh_out, array_merge($v->deepExport(), $sharedData));
                if ($count++ % self::TICK_SIZE === 0) {
                    $this->info(sprintf(
                        "Writing vouchers %d to %d, Mem: %s, elapsed time %f seconds",
                        $count,
                        $count + self::TICK_SIZE - 1,
                        TextFormatter::formatBytes(memory_get_usage()),
                        (microtime(true) - $time),
                    ));
                    $time = microtime(true);
                }
            }
        }

        $this->info("Total time: " . TextFormatter::secondsToTime(ceil(microtime(true) - $startTime)));
        fclose($fh_out);
    }
}

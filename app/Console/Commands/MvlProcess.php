<?php

namespace App\Console\Commands;

use App\Services\TextFormatter;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MvlProcess extends Command
{
    const TICK_SIZE=1000;

    const VOUCHER_NUMBER = 0;
    const VOUCHER_AREA = 1;
    const DATE_DISTRIBUTED = 2;
    const DISTRIBUTED_TO_CENTRE = 3;
    const DISTRIBUTED_TO_AREA = 4;
    const WAITING_FOR_COLLECTION = 5;
    const DATE_ISSUED = 6;
    const RVID = 7;
    const MAIN_CARER = 8;
    const DISBURSING_CENTRE = 9;
    const DATE_TRADER_RECORDED_VOUCHER = 10;
    const RETAILER_NAME = 11;
    const RETAIL_OUTLET = 12;
    const TRADERS_AREA = 13;
    const DATE_RECEIVED_FOR_REIMBURSEMENT = 14;
    const REIMBURSED_DATE = 15;
    const VOID_VOUCHER_DATE = 16;
    const VOID_REASON = 17;
    const DATE_FILE_WAS_DOWNLOADED = 18;

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
     * Execute the console command.
     */
    public function handle(): void
    {
        $in_file = $this->argument("file");
        $this->info(sprintf("Reading ids from %s", $in_file));
        if (!file_exists($in_file)) {
            $this->error(sprintf("File not found: %s", $in_file));
        }
        $out_file = dirname($in_file) . "/" . basename($in_file, ".txt") . ".csv";
        $this->info(sprintf("Writing ids to %s", $out_file));

        $fh_out = fopen($out_file, 'w');
        $count = 0;
        $startTime = microtime(true);
        $time = microtime(true);
        $lines = explode("\n", file_get_contents($in_file));
        foreach ($lines as $id) {
            $voucher = Voucher::find($id);
            // $this->info(sprintf("Voucher id=%d, code=%s", $voucher->id, $voucher->code));

            // Reject vouchers where the LAST state is not reimbursed
            if ($voucher->voucherHasBeenResurrected()) {
                continue;
            }

            $deliveries = $voucher->delivery;
            $deliveryCentre = $deliveries?->centre;
            $deliveryArea = $deliveries?->centre->sponsor;
            $bundle = $voucher->bundle;
            $family = $bundle?->registration->family;
            $disbursingCentre = $bundle?->registration->centre;

            $row = [];

            $row[self::VOUCHER_NUMBER] = $voucher->code;
            $row[self::VOUCHER_AREA] = $voucher->sponsor->name;
            $row[self::DATE_DISTRIBUTED] = $deliveries?->dispatched_at;
            $row[self::DISTRIBUTED_TO_CENTRE] = $deliveryCentre?->name;
            $row[self::DISTRIBUTED_TO_AREA] = $deliveryArea?->name;
            $row[self::WAITING_FOR_COLLECTION] = $bundle?->disbursed_at ? "True" : "False";
            $row[self::DATE_ISSUED] = $bundle?->disbursed_at;     //
            $row[self::RVID] = $voucher->rvid;
            $row[self::MAIN_CARER] = $family?->carers[0]->name;
            $row[self::DISBURSING_CENTRE] = $disbursingCentre?->name;
            $row[self::DATE_TRADER_RECORDED_VOUCHER] = $voucher->recordedOn->created_at;
            $row[self::RETAILER_NAME] = $voucher->trader->name;
            $row[self::RETAIL_OUTLET] = $voucher->trader->market->name;
            $row[self::TRADERS_AREA] = $voucher->trader->market->sponsor->name;
            $row[self::DATE_RECEIVED_FOR_REIMBURSEMENT] = $voucher->reimbursedOn->created_at;
            $row[self::REIMBURSED_DATE] = $voucher->updated_at;
            $row[self::VOID_VOUCHER_DATE] = null;   // Michelle agrees that a reimbursed voucher should never be
            $row[self::VOID_REASON] = null;         // voided.
            $row[self::DATE_FILE_WAS_DOWNLOADED] = Carbon::now();

            fputcsv($fh_out, $row);
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

        $this->info("Total time: " . TextFormatter::secondsToTime(ceil(microtime(true) - $startTime)));
        fclose($fh_out);
    }
}

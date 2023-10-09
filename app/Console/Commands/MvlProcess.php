<?php

namespace App\Console\Commands;

use App\VoucherState;
use Illuminate\Console\Command;

class MvlProcess extends Command
{
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
        $file = $this->argument("file");
        $this->info(sprintf("Reading ids from %s", $file));
        if (!file_exists($file)) {
            $this->error(sprintf("File not found: %s", $file));
        }

        $fh = fopen($file, 'r');
        if ($fh) {
            while (($id = fgets($fh)) !== false) {
                $voucherState = VoucherState::find($id);
                $voucher = $voucherState->voucher;
                $deliveries = $voucher->delivery;
                $deliveryCentre = $deliveries->centre;
                $deliveryArea = $deliveries->centre->sponsor;
                $bundle = $voucher->bundle;
                $family = $bundle->registration->family;
                $disbursingCentre = $bundle->registration->centre;

                $row = [];

                $row[self::VOUCHER_NUMBER] = $voucher->id;
                $row[self::VOUCHER_AREA] = $voucher->sponsor->name;
                $row[self::DATE_DISTRIBUTED] = $deliveries->dispatched_at;
                $row[self::DISTRIBUTED_TO_CENTRE] = $deliveryCentre->name;
                $row[self::DISTRIBUTED_TO_AREA] = $deliveryArea->name;
                $row[self::WAITING_FOR_COLLECTION] = $bundle->disbursed_at ? "True" : "False";
                $row[self::DATE_ISSUED] = $bundle->disbursed_at;
                $row[self::RVID] = $family->rvid;
                $row[self::MAIN_CARER] = $family->carers[0]->name;
                $row[self::DISBURSING_CENTRE] = $disbursingCentre->name;
                $row[self::DATE_TRADER_RECORDED_VOUCHER] = $voucher->recordedOn();
                $row[self::RETAILER_NAME] = $voucher->trader->name;
                $row[self::RETAIL_OUTLET] = $voucher->trader->market->name;
                $row[self::TRADERS_AREA] = $voucher->trader->market->sponsor->name;
                $row[self::DATE_RECEIVED_FOR_REIMBURSEMENT] = $voucher->reimbursedOn();
                $row[self::REIMBURSED_DATE] = $voucherState->created_at;
                $row[self::VOID_VOUCHER_DATE] = "If i've been reimbursed can I be voided?";
                $row[self::VOID_REASON] = "If i've been reimbursed can I be voided?";
                $row[self::DATE_FILE_WAS_DOWNLOADED] = "now";
            }
            // If voucher has a stete of retired, and get previous state for reason
            // if voucher->history()->get() .last() == "retrired"
            // pop
            // that's the reason

            // Voucher
            fclose($fh);
        }
    }
}

<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DB;
use Maatwebsite\Excel\Excel;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;

class CreateMasterVoucherLogReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:createMVLReport 
                            {--force : Execute without confirmation, eg for automation}
                            {--filename= : Filename, default: MVLReport}'
    ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates, encrypts and stores the MVL report under in /storage';

    /**
     * Filename for output;
     *
     * @var array|string
     */
    private $filename = 'MVLReport';

    /**
     * The report's query template
     * TODO: refactor this as eloquent lookups when it's finalised.
     *      This will eventually help with the fact we'll need to chunk data too.
     * @var string
     */
    private $report = <<<EOD
SELECT
  sponsors.name AS 'Area',
  vouchers.code AS 'Voucher Code',
  dispatch_date AS 'Dispatch Date',
  # bundles.disbursed_at as 'disbursed date',
  disbursed_at AS 'Disbursed Date',
  rvid AS 'RVID',
  payment_request_date AS 'Payment Request Date',
  trader_name AS 'Trader Name',
  market_name AS 'Retail Outlet',
  reimbursed_date AS 'Reimbursed Date'

FROM vouchers

  # Join for each voucher\'s sponsor name
  LEFT JOIN sponsors ON vouchers.sponsor_id = sponsors.id

  # Get our trader and market names.
  LEFT JOIN (
    SELECT traders.id,
           traders.name AS trader_name,
           markets.name AS market_name
    FROM traders
    LEFT JOIN markets ON traders.market_id = markets.id
  ) AS markets_query
    ON markets_query.id = vouchers.trader_id

  # Pivot dispatched date
  LEFT JOIN (
    SELECT voucher_states.voucher_id,
           voucher_states.created_at AS dispatch_date
    FROM voucher_states
    WHERE voucher_states.`to` = 'dispatched'
  ) AS dispatch_query
    ON dispatch_query.voucher_id = vouchers.id

  # Pivot payment_request date
  LEFT JOIN (
    SELECT voucher_states.voucher_id,
           voucher_states.created_at AS payment_request_date
    FROM voucher_states
    WHERE voucher_states.`to` = 'payment_pending'
  ) AS payment_request_query
    ON payment_request_query.voucher_id = vouchers.id

  # Pivot reimbursed date
  LEFT JOIN (
    SELECT voucher_states.voucher_id,
           voucher_states.created_at AS reimbursed_date
    FROM voucher_states
    WHERE voucher_states.`to` = 'reimbursed'
  ) AS reimburse_query
    ON reimburse_query.voucher_id = vouchers.id

  # Get fields we need to calculate RVID
  LEFT JOIN (
    SELECT bundles.id,
           bundles.disbursed_at AS disbursed_at,
           # LPAD will truncate values over 4 characters (or 9999 rvids).
           CONCAT(centres.prefix, LPAD(families.centre_sequence, 4, 0)) AS rvid

    FROM bundles
      LEFT JOIN registrations on bundles.registration_id = registrations.id
      LEFT JOIN families ON registrations.family_id = families.id
      # Need to join the primary carer here
      LEFT JOIN centres ON families.initial_centre_id = centres.id
  ) AS rvid_query
    ON rvid_query.id = vouchers.bundle_id
;
EOD;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Set filename
        $this->filename = (empty($this->option('filename')))
            ?: $this->option('filename');

        // Pause or carry on
        if ($this->option('force') || $this->warnUser()) {
            // Run the query

            $headers = [
                'Area',
                'Voucher Code',
                'Dispatch Date',
                'Disbursed Date',
                'RVID',
                'Payment Request Date',
                'Trader Name',
                'Retail Outlet',
                'Reimbursed Date'
            ];

            $rows = DB::select($this->report);
            $now = Carbon::now();

            /** @var LaravelExcelWriter $excelDoc */
            $excelDoc = Excel::create(
                $this->filename,
                function ($excel) use ($rows, $headers, $now) {
                    $excel->setTitle('Master Voucher Log');
                    $excel->setDescription('MVL generated at:' . $now->format('Y-m-d H:i:s'));
                    $excel->setManager('ARC');
                    $excel->setCompany(env('APP_URL'));
                    $excel->setCreator(env('APP_NAME'));
                    $excel->setKeywords([]);

                    // Produce entire sheet.
                    $excel->sheet(
                        'All Data',
                        function ($sheet) use ($rows, $headers) {
                            // Format page
                            $sheet->setOrientation('landscape');
                            $sheet->row(1, $headers);
                            $letters = range('A', 'Z');
                            $sheet->cells('A1:' . $letters[count($headers)-1] .'1', function ($cells) {
                                $cells->setBackground('#9ACD32')
                                    ->setFontWeight('bold');
                            });
                            $sheet->fromArray($rows, null, 'A2', false, false);
                        }
                    );
                }
            );

            $excelDoc->ext = 'xls';
            // TODO : build an ecryoted fileystem provider, and use it.
        }
    }

    /**
     * Warn the user before they execute.
     *
     * @return bool
     */
    public function warnUser()
    {
        $this->info('WARNING: This command will run a long, blocking query that will interrupt normal service use.');
        return $this->confirm('Do you wish to continue?');
    }
}

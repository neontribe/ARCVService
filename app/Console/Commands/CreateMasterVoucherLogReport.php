<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Log;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Sponsor;

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
     * @var string $report
     */
    private $report = <<<EOD
SELECT
  sponsors.name AS 'Area',
  vouchers.code AS 'Voucher Code',
  dispatch_date AS 'Dispatch Date',
  disbursed_at AS 'Disbursed Date',
  rvid AS 'RVID',
  pri_carer_name AS 'Participant',
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

  # Get fields relevant to bundles (pri_carer/RVID/disbursed_at)
  LEFT JOIN (
    SELECT bundles.id,
           bundles.disbursed_at AS disbursed_at,
           # LPAD will truncate values over 4 characters (or 9999 rvids).
           CONCAT(centres.prefix, LPAD(families.centre_sequence, 4, 0)) AS rvid,
           pri_carer_query.name as pri_carer_name
    FROM bundles
      LEFT JOIN registrations on bundles.registration_id = registrations.id
      LEFT JOIN families ON registrations.family_id = families.id
      LEFT JOIN centres ON families.initial_centre_id = centres.id
        
      # Need to join the Primary Carer here; Primary Carers are only relevant via bundles.
      LEFT JOIN (
        
        # We need the *first*, by self join grouping (classic technique, so SQLite can cope)
        SELECT t1.name, t1.family_id
        FROM carers t1
        INNER JOIN (
          SELECT MIN(id) as id
          FROM carers t3
          GROUP BY t3.family_id
        ) t2 ON t2.id = t1.id 
      ) AS pri_carer_query
        ON families.id = pri_carer_query.family_id
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
            ? $this->filename
            : $this->option('filename');

        // default exit code.
        $exitCode = 0;

        // Pause or carry on
        if ($this->option('force') || $this->warnUser()) {
            // Run the query

            $headers = [
                'Area',
                'Voucher Code',
                'Dispatch Date',
                'Disbursed Date',
                'RVID',
                'Participant',
                'Payment Request Date',
                'Trader Name',
                'Retail Outlet',
                'Reimbursed Date'
            ];

            // We're going to run this monster *once* and then split it into files.
            // We could run it once per sponsor; consider that if it becomes super-unwieldy.
            $rows = array_map(
                // Cast each element to proper array, not stdObjects as returned by DB::select()
                function ($value) {
                    return (array)$value;
                },
                DB::select($this->report)
            );
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

                    // First, produce entire sheet.
                    $excel->sheet(
                        'All Data',
                        function ($sheet) use ($rows, $headers) {
                            // Format page
                            $sheet->setOrientation('landscape');
                            $sheet->row(1, $headers);
                            $sheet->fromArray($rows, null, 'A2', false, false);
                        }
                    );

                    // Split up the rows into separate areas.
                    $areas = [];
                    foreach ($rows as $rowindex => $rowFields) {
                        $area = $rowFields['Area'];
                        if (!isset($areas[$area])) {
                            // Not met this area before? Add it to our list!
                            $areas[$area] = [];
                        }
                        // We're going to use "&" references to avoid memory issues - Hang on to your hat.
                        $areas[$area][] = &$rowFields;
                    }

                    // Make sheets for each.
                    foreach ($areas as $area => $areaRows) {
                        $excel->sheet(
                            $area,
                            function ($sheet) use ($areaRows, $headers) {
                                // Format page
                                $sheet->setOrientation('landscape');
                                $sheet->row(1, $headers);
                                $sheet->fromArray($areaRows, null, 'A2', false, false);
                            }
                        );
                    }

                }
            );
            // write a file, and see if it borks.
            $exitCode = ($this->writeOutput($excelDoc))
                ? 0
                : 1
            ;
        }
        // Set 0, above for expected outcomes
        exit($exitCode);
    }

    /**
     * Encrypts and stashes files.
     * @param LaravelExcelWriter $excelDoc
     * @return bool
     */
    public function writeOutput(LaravelExcelWriter $excelDoc)
    {
        try {
            $excelDoc->ext = 'ods';
            $fileContents = $excelDoc->string('ods'); // Throws Exception
            $filename = $this->filename .
                "." .
                $excelDoc->ext
            ;
            //Storage::disk('enc')->put($filename . ".enc", encrypt($fileContents));
            Storage::disk('enc')->put($filename, $fileContents);
        } catch (\Exception $e) {
            // Could be Storage or LaravelExcelWriterException related
            Log::error($e->getMessage());
            return false;
        }
        return true;
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

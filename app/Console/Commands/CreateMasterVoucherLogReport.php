<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Log;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use ZipStream\ZipStream;

class CreateMasterVoucherLogReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:createMVLReport 
                            {--force : Execute without confirmation, eg for automation}
                            {--no-zip : Don\'t wrap files in a single archive}
                            {--plain : Don\'t encrypt contents of files}
                            '
    ;

    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Creates, encrypts and stores the MVL report under in /storage';

    /**
     * The default disk we want.
     *
     * @var string $disk
     */
    private $disk = '';

    /**
     * The default archive name.
     *
     * @var string $archiveName
     */
    private $archiveName = '';

    /**
     * The sheet headers.
     *
     * @var array $headers
     */
    private $headers = [
        'Voucher Number',
        'Distributed to',
        'Date Issued',
        'Part ID',
        'Part Name',
        'Retail Outlet',
        'Date Received for Reimbursement',
        'Dispatch Date',
        'Area',
        'Trader Name',
        'Reimbursed Date',
        'Disbursing Centre'
    ];

    /**
     * The report's query template
     *
     * TODO: refactor this as eloquent lookups when it's finalised.
     *      This will eventually help with the fact we'll need to chunk data too.
     * @var string $report
     */
    private $report = <<<EOD
SELECT
  vouchers.code AS 'Voucher Number',
  # To be determined, we don't know this yet.
  '' AS 'Distributed to',
  disbursed_at AS 'Date Issued',
  rvid AS 'Part ID',
  pri_carer_name AS 'Part Name',
  market_name AS 'Retail Outlet',
  payment_request_date AS 'Date Received for Reimbursement',
  dispatch_date AS 'Dispatch Date',  
  sponsors.name AS 'Area',
  trader_name AS 'Trader Name',
  reimbursed_date AS 'Reimbursed Date',
  disbursing_centre AS 'Disbursing Centre'

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
           cb.name as disbursing_centre,
           # LPAD will truncate values over 4 characters (or 9999 rvids).
           CONCAT(cf.prefix, LPAD(families.centre_sequence, 4, 0)) AS rvid,
           pri_carer_query.name as pri_carer_name
    FROM bundles
      LEFT JOIN registrations on bundles.registration_id = registrations.id
      LEFT JOIN families ON registrations.family_id = families.id
      LEFT JOIN centres cf ON families.initial_centre_id = cf.id
      LEFT JOIN centres cb ON bundles.disbursing_centre_id = cb.id
        
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
     * CreateMasterVoucherLogReport constructor.
     * Sets some defaults
     */
    public function __construct()
    {
        parent::__construct();

        // Enable the secret stream protocol (ssw://). See SecretStreamWrapper for more information.
        // Registering here guarantees availability but there's probably a more Laravel-y place to put this.
        if (!in_array("ssw", stream_get_wrappers())) {
            stream_wrapper_register("ssw", "App\Wrappers\SecretStreamWrapper");
        }

        $this->archiveName = config('arc.mvl_filename');
        $this->disk = config('arc.mvl_disk');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Assess permission to continue
        if ($this->option('force') || $this->warnUser()) {
            // We're going to run this monster *once* and then split it into files.
            // We could run it once per sponsor; consider that if it becomes super-unwieldy.

            $rows = array_map(
                // Cast each element to proper array, not stdObjects as returned by DB::select()
                function ($value) {
                    return (array)$value;
                },
                DB::select($this->report)
            );

            // Set the disk
            $this->disk = ($this->option('plain'))
                ? 'local'
                : 'enc'
            ;

            // Make a zip archive, or not.
            if (!$this->option('no-zip')) {
                // Setup an archive
                $storagePath = Storage::disk($this->disk)->getAdapter()->getPathPrefix();
                // Open the file for writing at the correct location
                $path = $storagePath . '/' . $this->archiveName;

                // Encrypt the output stream if the user hasn't asked for it to be plain.
                if (!$this->option('plain')) {
                    $path = 'ssw://' . $path;
                }

                // Stream directly to what is either a file or a file wrapped in a secret stream.
                $za = new ZipStream(null, [
                    ZipStream::OPTION_SEND_HTTP_HEADERS => false,
                    ZipStream::OPTION_OUTPUT_STREAM => fopen($path, 'w'),
                ]);
            } else {
                $za = null;
            }

            // Create and write a sheet for all data.
            $excelDoc = $this->createWorkSheet('ALL', $rows);
            $this->writeOutput($excelDoc, $za);

            // Split up the rows into separate areas.
            $areas = [];
            // We're going to use "&" references to avoid memory issues - Hang on to your hat.
            foreach ($rows as $rowindex => &$rowFields) {
                $area = $rowFields['Area'];
                if (!isset($areas[$area])) {
                    // Not met this Area before? Add it to our list!
                    $areas[$area] = [];
                }
                $areas[$area][] = &$rowFields;
            }

            // Make sheets for each area and write them
            foreach ($areas as $area => $areaRows) {
                $excelDoc = $this->createWorkSheet($area, $areaRows);
                $this->writeOutput($excelDoc, $za);
            }

            if ($za) {
                // End the zip stream with something meaningful.
                $za->finish();
            }
        }
        // Set 0, above for expected outcomes
        exit(0);
    }

    /**
     * Encrypts and stashes files.
     *
     * @param LaravelExcelWriter $excelDoc
     * @param ZipStream|null $za
     * @return bool
     */
    public function writeOutput(LaravelExcelWriter $excelDoc, ZipStream $za = null)
    {
        try {
            $excelDoc->ext = 'csv';

            /*
             * TODO: If we move to a spreadsheet library that gives us an output stream, we need never hold the entire
             * contents of the resulting file in memory; it could be streamed directly through the zip.
             */
            $fileContents = $excelDoc->string($excelDoc->ext); // Throws Exception
            $filename = preg_replace('/\s+/', '_', $excelDoc->getSheet()->getTitle()) .
                "." .
                $excelDoc->ext;

            if ($za) {
                // Encryption, if enabled, is handled at the creation of our ZipStream. The stream is directed through
                // an encrypted wrapper before it writes to the disk.
                $za->addFile($filename, $fileContents);
            } else {
                // Ensure that the user intends to write the output plain to the disk. This is highly unlikely in production.
                if ($this->option('plain')) {
                    Storage::disk($this->disk)->put($filename, $fileContents);
                } else {
                    // TODO : Consider redesigning the CLI such that this is not even possible to express.
                    Log::error('Encrypted output is not supported with --no-zip, consider adding --plain if you\'re SURE you want to write this data to the disk unencrypted.');
                }
            }
        } catch (\Exception $e) {
            // Could be Storage or LaravelExcelWriterException related
            Log::error($e->getMessage());
            Log::error(class_basename($this) . ": Failed to write file for '" . $excelDoc->getTitle() ."'");
            exit(1);
        }
        return true;
    }

    /**
     * Creates an excel file from an array of data.
     *
     * @param $name
     * @param $rows
     * @return LaravelExcelWriter
     */

    public function createWorkSheet($name, $rows)
    {
        $now = Carbon::now();

        /** @var LaravelExcelWriter $excelDoc */
        $excelDoc = Excel::create(
            preg_replace('/\s+/', '_', $name),
            function ($excel) use ($name, $rows, $now) {
                $excel->setTitle('MVL - ' . $name);
                $excel->setDescription('generated at:' . $now->format('Y-m-d H:i:s'));
                $excel->setManager('ARC');
                $excel->setCompany(env('APP_URL'));
                $excel->setCreator(env('APP_NAME'));
                $excel->setKeywords([]);

                // First, produce entire sheet.
                $excel->sheet(
                    $name,
                    function ($sheet) use ($rows) {
                        // Format page
                        $sheet->setOrientation('landscape');
                        $sheet->row(1, $this->headers);
                        $sheet->fromArray($rows, null, 'A2', false, false);
                    }
                );
            }
        );
        return $excelDoc;
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

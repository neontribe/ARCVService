<?php

namespace App\Console\Commands;

use App\Services\TextFormatter;
use DateTime;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Log;
use PDO;
use ZipStream\Exception\OverflowException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class CreateMasterVoucherLogReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:createMVLReport
                            {--progress : show progress}
                            {--force : Execute without confirmation, eg for automation}
                            {--no-zip : Don\'t wrap files in a single archive}
                            {--plain : Don\'t encrypt contents of files}
                            {--date-from=year-start : The start date for this report, default=year-start}
                            {--date-to=now : The end date for this report, default=today}
                            {--row-limit=999990 : The end date for this report, default=today}
                            ';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Creates, encrypts and stores the MVL report under in /storage';

    /**
     * @var int Row limit per file. excel (and libreoffice) tops out at 10^6
     */
    private int $rowLimit = 999990;
    /**
     * The default disk we want.
     *
     * @var string $disk
     */
    private string $disk;
    /**
     * The default archive name.
     *
     * @var string $archiveName
     */
    private string $archiveName;
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
     * @var ZipStream $za ;
     */
    private ZipStream $za;

    // Excel can't deal with large CSVs
    private $zaOutput;
    /**
     * The report's query template
     *
     * TODO: refactor this as eloquent lookups when it's finalised.
     *      This will eventually help with the fact we'll need to chunk data too.
     * @var string $report
     */
    private string $report = <<<EOD
SELECT
  vouchers.code AS 'Voucher Number',
  voucher_sponsor.name AS 'Voucher Area',
  DATE_FORMAT(deliveries.dispatched_at, '%d/%m/%Y') as 'Date Distributed',
  delivery_centres.name AS 'Distributed to Centre',
  delivery_areas.name AS 'Distributed to Area',
  CASE
      WHEN (disbursed_at is null) AND (pri_carer_name is not null) THEN 'True'
      WHEN (disbursed_at is not null) AND (pri_carer_name is not null) THEN 'False'
  END
  AS 'Waiting for collection',
  DATE_FORMAT(disbursed_at, '%d/%m/%Y') AS 'Date Issued',
  rvid AS 'RVID',
  pri_carer_name AS 'Main Carer',
  disbursing_centre AS 'Disbursing Centre',
  (select date_format(created_at, '%d/%m/%Y') from voucher_states where vouchers.id = voucher_id and `to` = 'recorded' order by id desc limit 1)  AS 'Date Trader Recorded Voucher',
  trader_name AS 'Retailer Name',
  market_name AS 'Retail Outlet',
  market_area AS 'Trader\'s Area',
  (select min(date_format(voucher_states.created_at, '%d/%m/%Y'))
    FROM voucher_states
    WHERE voucher_states.`to` = 'payment_pending'
    and vouchers.id = voucher_id
    group by voucher_id
    limit 1) AS 'Date Received for Reimbursement',
  (select date_format(created_at, '%d/%m/%Y') from voucher_states where vouchers.id = voucher_id and `to` = 'reimbursed' order by id desc limit 1) AS 'Reimbursed Date',
  (select created_at from voucher_states where vouchers.id = voucher_id and `to` = 'retired' order by id desc limit 1)  AS 'Void Voucher Date',
  (select created_at from voucher_states where vouchers.id = voucher_id and `to` in ('expired', 'voided') order by id desc limit 1) AS 'Void Reason',
  CURDATE() AS 'Date file was Downloaded'
FROM vouchers
  # get the voucher sponsor.
  LEFT JOIN sponsors as voucher_sponsor on vouchers.sponsor_id = voucher_sponsor.id
  # Get our trader, market and sponsor names
  LEFT JOIN (
    SELECT traders.id,
           traders.name AS trader_name,
           markets.name AS market_name,
           sponsors.name AS market_area
    FROM traders
    LEFT JOIN markets ON traders.market_id = markets.id
    LEFT JOIN sponsors on markets.sponsor_id = sponsors.id
  ) AS markets_query
    ON markets_query.id = vouchers.trader_id
  # Get fields relevant to voucher's delivery (Date, target centre and centres area)
  LEFT JOIN deliveries ON vouchers.delivery_id = deliveries.id
  LEFT JOIN
    centres AS delivery_centres
        ON deliveries.centre_id = delivery_centres.id
  LEFT JOIN
     sponsors as delivery_areas
        ON delivery_areas.id = delivery_centres.sponsor_id
  # Get fields relevant to bundles (pri_carer/RVID/disbursed_at,disbursing_centre)
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

order by vouchers.id desc
LIMIT ?
OFFSET ?
EOD;

    /**
     * @var int The end time to truncate voucher export at.
     */
    private int $timeTo;
    /**
     * @var int The start time to truncate voucher export from.
     */
    private int $timeFrom;
    /**
     * @var bool If true the CLI recieves updates on the export process
     */
    private bool $progress;

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
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        // Set up export
        $this->initSettings();

        $startTime = strtotime('now');
        $rows = [];

        // DEV Code while I was testing file writes, the full command took ~20 mins to run
        // if (file_exists(sys_get_temp_dir() . "/MLVCache.ser") && config('app.env') === 'local')
        //    $rows = unserialize(file_get_contents(sys_get_temp_dir() . "/MLVCache.ser"));

        // Assess permission to continue
        if (($this->option('force') || $this->warnUser()) && count($rows) === 0) {
            // We could run it once per sponsor; consider that if it becomes super-unwieldy.

            $continue = true;
            $lookups = 0;
            $chunkSize = 50000;

            $mem = memory_get_usage();
            $this->log("starting query, mem :" . $mem);

            while ($continue) {
                $chunk = $this->execQuery($chunkSize, $chunkSize * $lookups);

                // when we're at the tail, quit next round
                if (count($chunk) < $chunkSize) {
                    $continue = false;
                }

                // clean the vouchers in this chunk
                // should be done is SQL
                foreach ($chunk as $k => $voucher) {
                    if ($this->rejectThisVoucher($voucher)) {
                        unset($chunk[$k]);
                    }
                }

                // should be faster
                $rows = [...$rows, ...$chunk];
                /*  $rows = array_merge($rows, $chunk); */

                $lookups++;

                // clean memory
                $chunk = null;
                unset($chunk);
                $mem = memory_get_usage();

                $this->log(sprintf(
                    "MVL exporting chunk %d of %d records. Memory usage: %s",
                    $lookups, $chunkSize, TextFormatter::formatBytes($mem)
                ));
            }

            $this->log("finished query, meme:" . TextFormatter::formatBytes($mem));
            $this->log("using " . $this->disk);
            $this->log("beginning file write, mem:" . memory_get_usage());
        }

        // DEV Code while I was testing file writes, the full command took ~20 mins to run
        // if (!file_exists(sys_get_temp_dir() . "/MLVCache.ser") && config('app.env') === 'local')
        //    file_put_contents(sys_get_temp_dir() . "/MLVCache.ser", serialize($rows));

        $this->writeMultiPartMVL($rows);

        // Split up the rows into separate areas.
        $this->writeAreaFiles($rows);

        if (isset($this->za, $this->zaOutput)) {
            // End the zip stream with something meaningful.
            try {
                $this->za->finish();
            } catch (OverflowException $e) {
                Log::error($e->getMessage());
                Log::error("Overflow when attempting to finish a significantly large Zip file");
                exit(1);
            }

            // Manually close our stream. This is especially important when the stream is encrypted, as a little
            // extra data is spat out.
            fclose($this->zaOutput);
        }
        if ($this->progress) {
            $this->output->write('', true);
            $this->log(sprintf("Execution time: %d seconds", strtotime('now') - $startTime));
        }
        // Set 0, above for expected outcomes
        exit(0);
    }

    private function log($message, $level = "info") {
        switch ($level) {
            case "debug":
                Log::debug($message);
                if ($this->progress) $this->info($message);
                break;
            default:
            case "info":
                Log::info($message);
                if ($this->progress) $this->info($message);
                break;
            case "warn":
                Log::warning($message);
                if ($this->progress) $this->warn($message);
                break;
            case "error":
                Log::error($message);
                if ($this->progress) $this->error($message);
                break;
            case "critical":
                Log::critical($message);
                if ($this->progress) $this->error($message);
                break;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function initSettings(): void
    {
        // Set the disk
        $this->disk = ($this->option('plain'))
            ? 'local'
            : 'enc';

        $this->progress = $this->option('progress');
        $this->rowLimit = $this->option('row-limit');

        // Validate dates
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        if ($dateFrom != "year-start") {
            if (!$this->validateDate($dateFrom)) {
                $this->error('Unable to parse from date: ' . $this->option('date-from'));
            }
            $dateFrom = date('Y-m-d', strtotime($dateFrom));
        } else {
            $thisYearsApril = Carbon::parse('april')->startOfMonth();
            $years = ($thisYearsApril->isPast()) ? 2 : 1;
            $this->$dateFrom = $thisYearsApril->subYearsNoOverflow($years)->format('Y-m-d');
        }

        if ($dateTo != "now") {
            if (!$this->validateDate($dateTo)) {
                $this->error('Unable to parse to date: ' . $this->option('date-to'));
            }
            $dateTo = date('Y-m-d', strtotime($dateTo));
        } else {
            $dateTo = date('Y-m-d');
        }

        if ($dateFrom > $dateTo) {
            throw new Exception(sprintf("Looks like the from/to dates are reversed, %s > %s.", $dateFrom, $dateTo));
        }

        $this->timeTo = strtotime($dateTo);
        $this->timeFrom = strtotime($dateFrom);
        $this->log(sprintf("Searching for records from %s to %s", $dateFrom, $dateTo));

        if (!$this->option('no-zip')) {
            // Open the file for writing at the correct location
            $path = Storage::path($this->disk) . '/' . $this->archiveName;

            // Encrypt the output stream if the user hasn't asked for it to be plain.
            if (!$this->option('plain')) {
                $path = 'ssw://' . $path;
            }

            // Stream directly to what is either a file or a file wrapped in a secret stream.
            $options = new Archive();
            $this->zaOutput = fopen($path, 'w');
            $options->setOutputStream($this->zaOutput);
            $this->za = new ZipStream(null, $options);
        }
    }

    function validateDate($date, $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * Warn the user before they execute.
     *
     * @return bool
     */
    public function warnUser(): bool
    {
        $this->log('WARNING: This command will run a long, blocking query that will interrupt normal service use.', "warn");
        return $this->confirm('Do you wish to continue?');
    }

    /**
     * returns a query chunk
     * @param int $limit
     * @param int $offset
     * @return bool|array
     */
    private function execQuery(int $limit, int $offset): bool|array
    {
        $s = DB::connection()
            ->getPdo()
            ->prepare($this->report);
        $s->execute([$limit, $offset]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * This should be part of the query
     * @param $voucher
     * @return bool
     */
    public function rejectThisVoucher($voucher): bool
    {
        // exit early if we don't have a Reimbursed Date
        if (is_null($voucher['Reimbursed Date'])) {
            return false;
        }
        $reimbursedDate = strtotime(
            DateTime::createFromFormat(
                'd/m/Y',
                $voucher['Reimbursed Date']
            )->format('Y-m-d')
        );

        // return true, if any of these are true
        return
            // are all the fields we care about null?
            $this->containsOnlyNull($voucher) ||
            // is this date filled?
            !is_null($voucher['Void Voucher Date']) ||
            // is this date dilled *and* less than the cut-off date
            $reimbursedDate < $this->timeFrom || $reimbursedDate > $this->timeTo;
    }

    /**
     * Adapted from https://stackoverflow.com/a/67977923
     * @param array $array
     * @return bool
     */
    public function containsOnlyNull(array $array): bool
    {
        // iterate over each field
        foreach ($array as $key => $value) {
            // if the field is filled
            if ($value !== null &&
                // and it's NOT inthis set
                !in_array(
                    $key,
                    [
                        'Voucher Number',
                        'Voucher Area',
                        'Date file was Downloaded',
                        'Void Voucher Date' // this having a date is dealt with elsewhere
                    ]
                )
            ) {
                // then it's got at least one value in, so return false;
                return false;
            }
        }
        // if we get to the end, and it's all nulls
        return true;
    }

    /**
     * @param $rows
     * @return void
     */
    public function writeMultiPartMVL($rows): void
    {
        // set some loop controls
        $nextFile = true;
        $fileNum = 1;
        $last_key = array_key_last($rows);

        // go over each row
        $rowCounter = 0;
        foreach ($rows as $index => $row) {
            // do we need to open a new file?
            if ($nextFile || !isset($fileHandleAll)) {
                // start a new file and make some headers
                $fileHandleAll = fopen('php://temp', 'r+');
                fputcsv($fileHandleAll, $this->headers);
            }

            // add lines to the file
            fputcsv($fileHandleAll, $row);
            $rowCounter++;
            // calculate the file number
            $calcFileNum = 1 + intdiv($index, $this->rowLimit);
            // has it increased?
            $nextFile = ($calcFileNum > $fileNum);

            if ($nextFile || $last_key === $index) {
                // stash and close this file
                rewind($fileHandleAll);
                $this->log(sprintf("Writing %d records to %s", $rowCounter, 'PART' . $fileNum));
                $this->writeOutput('PART' . $fileNum, stream_get_contents($fileHandleAll));
                fclose($fileHandleAll);
                $rowCounter = 0;
                // update the fileNum
                $fileNum = $calcFileNum;
            }
        }
    }

    /**
     * Encrypts and stashes files.
     *
     * @param String $name
     * @param String $csv
     * @return bool
     */
    public function writeOutput(string $name, string $csv): bool
    {
        try {
            $filename = sprintf("%s.csv", preg_replace('/\s+/', '_', $name));

            if (isset($this->za)) {
                // Encryption, if enabled, is handled at the creation of our ZipStream. The stream is directed through
                // an encrypted wrapper before it writes to the disk.
                $this->za->addFile($filename, $csv);
            } elseif ($this->option('plain')) {
                Storage::disk($this->disk)->put($filename, $csv);
            } else {
                // TODO : Consider redesigning the CLI such that this is not even possible to express.
                Log::error('Encrypted output is not supported with --no-zip, consider adding --plain if you\'re SURE you want to write this data to the disk unencrypted.');
            }
        } catch (Exception $e) {
            // Could be Storage or LaravelExcelWriterException related
            Log::error($e->getMessage());
            Log::error(class_basename($this) . ": Failed to write file for '" . $csv . "'");
            exit(1);
        }
        return true;
    }

    /**
     * @param $rows
     * @return void
     */
    public function writeAreaFiles($rows): void
    {
        $areas = [];
        // We're going to use "&" references to avoid memory issues - Hang on to your hat.
        foreach ($rows as &$rowFields) {
            $area = $rowFields['Voucher Area'];
            if (!isset($areas[$area])) {
                // Not met this Area before? Add it to our list!
                $areas[$area] = [];
            }
            $areas[$area][] = &$rowFields;
        }

        // Make sheets for each area and write them
        foreach ($areas as $area => $areaRows) {
            $fileHandleArea = fopen('php://temp', 'r+');
            fputcsv($fileHandleArea, $this->headers);
            foreach ($areaRows as $row) {
                fputcsv($fileHandleArea, $row);
            }
            rewind($fileHandleArea);
            $this->writeOutput($area, stream_get_contents($fileHandleArea));
            fclose($fileHandleArea);
        }
    }
}

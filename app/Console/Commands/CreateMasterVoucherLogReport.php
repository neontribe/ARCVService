<?php

namespace App\Console\Commands;

use DateTime;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
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
                            {--force : Execute without confirmation, eg for automation}
                            {--no-zip : Don\'t wrap files in a single archive}
                            {--plain : Don\'t encrypt contents of files}
                            {--date-from=all : The start date for this report, default=all records}
                            {--date-to=now : The end date for this report, default=today}
                            ';

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
    private $disk;

    /**
     * The default archive name.
     *
     * @var string $archiveName
     */
    private $archiveName;

    /**
     * The sheet headers.
     *
     * @var array $headers
     */
    private $headers = [
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
     * The report's query template
     *
     * TODO: refactor this as eloquent lookups when it's finalised.
     *      This will eventually help with the fact we'll need to chunk data too.
     * @var string $report
     */
    private $report = <<<EOD
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
  # Get fields relevant to voucher's delivery (Date, target centre and centre's area)
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
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        if ($dateFrom != "all") {
            $dateFrom = date('Y-m-d', strtotime($dateFrom));
        } else {
            $dateFrom = "1970-01-01";
        }

        if ($dateTo == "now") {
            $dateTo = date('Y-m-d');
        } else {
            $dateTo = date('Y-m-d', strtotime($dateTo));
        }

        if ($dateTo == "1970-01-01") {
            $this->error('Unable to parse to date: ' . $this->option('date-to'));
            return 1;
        } elseif ($dateFrom == "1970-01-01") {
            $this->error('Unable to parse to date: ' . $this->option('date-from'));
            return 2;
        }

        if ($dateFrom > $dateTo) {
            $this->info('Looks like the from/to dates are reversed, swapping them.');
            $d = $dateTo;
            $dateTo = $dateFrom;
            $dateFrom = $d;
        }

        $timeTo = strtotime($dateTo);
        $timeFrom = strtotime($dateFrom);

        $this->info(sprintf("Searching for records from %s to %s", $dateFrom, $dateTo));

        // Assess permission to continue
        if ($this->option('force') || $this->warnUser()) {
            // We could run it once per sponsor; consider that if it becomes super-unwieldy.

            $rows = [];
            $continue = true;
            $lookups = 0;
            $chunkSize = 50000;

            $mem = memory_get_usage();
            Log::info("starting query, mem :" . $mem);

            $included = 0;
            $skipped = 0;

            while ($continue) {
                $chunk = $this->execQuery($chunkSize, $chunkSize * $lookups);

                if (count($chunk) < $chunkSize) {
                    $continue = false;
                }

                foreach ($chunk as $k => $voucher) {
                    if ($this->containsOnlyNull($voucher)) {
                        unset($chunk[$k]);
                        continue;
                    }
                    if (!is_null($voucher['Void Voucher Date'])) {
                        unset($chunk[$k]);
                        continue;
                    }
                    if (!is_null($voucher['Reimbursed Date'])) {
                        $date = strtotime(DateTime::createFromFormat('d/m/Y', $voucher['Reimbursed Date'])->format('Y-m-d'));
                        $toOld = $date < $timeFrom;
                        $toNew =  $date > $timeTo;
//                        $this->info(sprintf("from %s, to %s, date %s", $dateFrom, $dateTo, $date->format('Y-m-d')));
                        if ($toOld || $toNew) {
                            unset($chunk[$k]);
                            $skipped++;
                        }
                        else {
                            $included++;
                        }
                    }
                }

                $this->info(sprintf("skipped %s, included %s", $skipped, $included));

                $rows = array_merge($rows, $chunk);
                $lookups++;
                $chunk = null;
                unset($chunk);

                $mem = memory_get_usage();
                Log::info($lookups . 'x' . $chunkSize . ', skip ' . $chunkSize * $lookups . ",mem :" . $mem);
            }

            Log::info("finished query, meme:" . $mem);

            // Set the disk
            $this->disk = ($this->option('plain'))
                ? 'local'
                : 'enc';

            Log::info("using " . $this->disk);

            // Make a zip archive, or not.
            if (!$this->option('no-zip')) {
                // Setup an archive
                $storagePath = Storage::path($this->disk);
                // Open the file for writing at the correct location
                $path = $storagePath . '/' . $this->archiveName;

                // Encrypt the output stream if the user hasn't asked for it to be plain.
                if (!$this->option('plain')) {
                    $path = 'ssw://' . $path;
                }

                // Stream directly to what is either a file or a file wrapped in a secret stream.
                $options = new Archive();
                $zaOutput = fopen($path, 'w');
                $options->setOutputStream($zaOutput);
                $za = new ZipStream(null, $options);
            } else {
                $za = null;
                $zaOutput = null;
            }

            Log::info("beginning file write, mem:" . memory_get_usage());

            // Create and write a sheet for first half of data.
            $fileHandleAll = fopen('php://temp', 'r+');
            fputcsv($fileHandleAll, $this->headers);
            foreach ($rows as $index => $row) {
                if ($index <= count($rows) / 2) {
                    fputcsv($fileHandleAll, $row);
                }
            }

            rewind($fileHandleAll);
            $this->writeOutput('PART1', stream_get_contents($fileHandleAll), $za);
            fclose($fileHandleAll);

            // Create and write a sheet for second half of data.
            $fileHandleAll = fopen('php://temp', 'r+');
            fputcsv($fileHandleAll, $this->headers);
            foreach ($rows as $index => $row) {
                if ($index > count($rows) / 2) {
                    fputcsv($fileHandleAll, $row);
                }
            }
            rewind($fileHandleAll);
            $this->writeOutput('PART2', stream_get_contents($fileHandleAll), $za);
            fclose($fileHandleAll);

            // Split up the rows into separate areas.
            $areas = [];
            // We're going to use "&" references to avoid memory issues - Hang on to your hat.
            foreach ($rows as $rowindex => &$rowFields) {
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
                $this->writeOutput($area, stream_get_contents($fileHandleArea), $za);
                fclose($fileHandleArea);
            }

            if ($za) {
                // End the zip stream with something meaningful.
                try {
                    $za->finish();
                } catch (OverflowException $e) {
                    Log::error($e->getMessage());
                    Log::error("Overflow when attempting to finish a significantly large Zip file");
                    exit(1);
                }

                // Manually close our stream. This is especially important when the stream is encrypted, as a little
                // extra data is spat out.
                fclose($zaOutput);
            }
        }
        // Set 0, above for expected outcomes
        exit(0);
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

    private function execQuery($limit, $offset)
    {
        $s = DB::connection()
            ->getPdo()
            ->prepare($this->report);
        $s->execute([$limit, $offset]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Encrypts and stashes files.
     *
     * @param String $name
     * @param String $csv
     * @param ZipStream|null $za
     * @return bool
     */
    public function writeOutput(string $name, string $csv, ZipStream $za = null)
    {
        try {
            $filename = sprintf("%s.csv", preg_replace('/\s+/', '_', $name));

            if ($za) {
                // Encryption, if enabled, is handled at the creation of our ZipStream. The stream is directed through
                // an encrypted wrapper before it writes to the disk.
                $za->addFile($filename, $csv);
            } else {
                // Ensure that the user intends to write the output plain to the disk. This is highly unlikely in production.
                if ($this->option('plain')) {
                    Storage::disk($this->disk)->put($filename, $csv);
                } else {
                    // TODO : Consider redesigning the CLI such that this is not even possible to express.
                    Log::error('Encrypted output is not supported with --no-zip, consider adding --plain if you\'re SURE you want to write this data to the disk unencrypted.');
                }
            }
        } catch (Exception $e) {
            // Could be Storage or LaravelExcelWriterException related
            Log::error($e->getMessage());
            Log::error(class_basename($this) . ": Failed to write file for '" . $csv->getTitle() . "'");
            exit(1);
        }
        return true;
    }

    // Adapted from https://stackoverflow.com/a/67977923
    public function containsOnlyNull(array $array): bool
    {
        foreach ($array as $key => $value) {
            if ($key !== 'Voucher Number' && $key !== 'Voucher Area' && $key !== 'Date file was Downloaded' && $value !== null) {
                return false;
            }
        }
        return true;
    }
}

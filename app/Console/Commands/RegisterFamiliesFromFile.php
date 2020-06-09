<?php

namespace App\Console\Commands;

use App\Carer;
use App\Centre;
use App\Child;
use App\Family;
use App\Registration;
use App\CentreUser;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use SplFileObject;

class RegisterFamiliesFromFile extends Command
{
    /**
     * @var int CSVIDX_PRIVACY CSV column index of the privacy value
     */
    private const CSVIDX_PRIVACY = 0;
    /**
     * @var int CSVIDX_SEQ_REFERENCE CSV column index of the seq reference value
     */
    private const CSVIDX_SEQ_REFERENCE = 1;
    /**
     * @var int CSVIDX_PRIMARY_CARER_NAME CSV column index of the primary carer value
     */
    private const CSVIDX_PRIMARY_CARER_NAME = 2;
    /**
     * @var int CSVIDX_SECONDARY_COLLECTOR CSV column index of the secondary collector value
     */
    private const CSVIDX_SECONDARY_COLLECTOR = 3;
    /**
     * @var int CSVIDX_SIGNUP_DATE CSV column index of the signup date value
     */
    private const CSVIDX_SIGNUP_DATE = 4;
    /**
     * @var int CSVIDX_CHILD_1 CSV column index of the child 1 value
     */
    private const CSVIDX_CHILD_1 = 5;
    /**
     * @var int CSVIDX_CHILD_2 CSV column index of the child 2 value
     */
    private const CSVIDX_CHILD_2 = 6;
    /**
     * @var int CSVIDX_CHILD_3 CSV column index of the child 3 value
     */
    private const CSVIDX_CHILD_3 = 7;
    /**
     * @var int CSVIDX_CHILD_4 CSV column index of the child 4 value
     */
    private const CSVIDX_CHILD_4 = 8;

    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var Centre $centre */
    private $centre;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:registerFamiliesFromFile
                                {file : CSV file of family registrations to import, one per line}
                                {prefix : the children\'s centre prefix}  
                                {email : email address of CentreUser who\'s responsible for this}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports and registers families against a CC from a CSV against a Children\'s Centre';

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
        // Load the CSV
        $filename = $this->argument('file');
        $csvFile = new SplFileObject($filename, 'r');
        $csvFile->seek(PHP_INT_MAX);
        if ($csvFile->key() == 0) {
            exit("There are no registrations.\n");
        }
        $bar = $this->output->createProgressBar($csvFile->key());
        $csvFile->rewind();

        // Search for existing centre user and centre
        $this->centreUser = CentreUser::query('email', $this->argument('email'))->first();
        $this->centre = Centre::query('prefix', $this->argument('prefix'))->first();

        switch (true) {
            case (!$this->centreUser):
                exit("Can't find that User.\n");
                break;
            case (!$this->centre):
                exit("Can't find the Centre.\n");
                break;
        }

        // Check the user is happy to proceed
        if (!$this->warnUser()) {
            exit("Exit without change.\n");
        }

        // Log the centreuser in.
        Auth::login($this->centreUser);
        if (!Auth::check()) {
            exit("Failed to login.\n");
        }

        // Is it one of the CentreUser's Centres?
        if (!Auth::user()->isRelevantCentre($this->centre)) {
            exit("User cannot access that Centre.\n");
        }

        // The following check is a bit wierd.  It seems that the last fgetcsv from the empty row returns a one
        // elemnt array with a null in it.
        while (count($row = $csvFile->fgetcsv()) != 1) {
            $signup_date = Carbon::createFromFormat('Y-m-d', $row[self::CSVIDX_SIGNUP_DATE])->startOfDay();

            $registration = new Registration([
                'consented_on' => $signup_date,
                'eligibility' => 'other',
            ]);

            // Make a new family.
            $family = new Family();

            $family->initial_centre_id = $this->centre->id;

            // Cut the prefix and cast left-padded 0's to an int.
            $family->centre_sequence = (int)preg_replace(
                "/" . $this->centre->prefix . "/",
                "",
                $row[self::CSVIDX_SEQ_REFERENCE]
            );

            // Make some carers
            $carerModels = [new Carer(['name' => $row[self::CSVIDX_PRIMARY_CARER_NAME]])];
            if ($row[self::CSVIDX_SECONDARY_COLLECTOR]) {
                $carerModels[] = new Carer(['name' => $row[self::CSVIDX_SECONDARY_COLLECTOR]]);
            }

            $childModels = [];
            if (isset($row[self::CSVIDX_CHILD_1]) && $row[self::CSVIDX_CHILD_1] != null) {
                $childModels[] = $this->createChild($row[self::CSVIDX_CHILD_1]);
            }
            if (isset($row[self::CSVIDX_CHILD_2]) && $row[self::CSVIDX_CHILD_2] != null) {
                $childModels[] = $this->createChild($row[self::CSVIDX_CHILD_2]);
            }
            if (isset($row[self::CSVIDX_CHILD_3]) && $row[self::CSVIDX_CHILD_3] != null) {
                $childModels[] = $this->createChild($row[self::CSVIDX_CHILD_3]);
            }
            if (isset($row[self::CSVIDX_CHILD_4]) && $row[self::CSVIDX_CHILD_4] != null) {
                $childModels[] = $this->createChild($row[self::CSVIDX_CHILD_4]);
            }

            try {
                DB::transaction(function () use ($registration, $family, $carerModels, $childModels) {
                    $family->save();
                    $family->carers()->saveMany($carerModels);
                    $family->children()->saveMany($childModels);
                    $registration->family()->associate($family);
                    $registration->centre()->associate($this->centre);
                    $registration->save();
                });
            } catch (\Throwable $e) {
                // Oops! Log that
                dd($e->getTraceAsString());
            }

            $bar->advance();
        }
        $bar->finish();
        exit("Done.\n");
    }

    private function createChild($dob)
    {
        $month_of_birth = Carbon::createFromFormat(
            'Y-m-d',
            $dob . '-01'
        )->startOfDay();

        return new Child([
            'born' => $month_of_birth->isPast(),
            'dob' => $month_of_birth->toDateTimeString(),
        ]);
    }

    public function warnUser()
    {
        $this->info('This unsafe command will alter the database to add records.');
        return $this->confirm('Do you wish to continue?');
    }

}

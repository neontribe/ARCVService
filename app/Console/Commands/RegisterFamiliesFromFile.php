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
use Excel;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Collections\CellCollection;
use Maatwebsite\Excel\Collections\RowCollection;

class RegisterFamiliesFromFile extends Command
{
    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var Centre $centre */
    private $centre;

    /** @var RowCollection $rows */
    private $rows;

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
        $this->rows = Excel::load($this->argument('file'))->get();

        // Search for existing centreuser and centre
        $this->centreUser = CentreUser::where('email', $this->argument('email'))->first();
        $this->centre = Centre::where('prefix', $this->argument('prefix'))->first();

        switch (true) {
            case (!$this->centreUser):
                exit("Can't find that User.\n");
                break;
            case (!$this->centre):
                exit("Can't find the Centre.\n");
                break;
            case ($this->rows->isEmpty()):
                exit("There are no registrations.\n");
                break;
            default:
                // Check the user is happy to proceed
                if (!$this->warnUser()) {
                    exit("Exit without change.\n");
                };

                // Log the centreuser in.
                Auth::login($this->centreUser);
                if (!Auth::check()) {
                    exit("Failed to login.\n");
                };

                // Is it one of the CentreUser's Centres?
                if (!Auth::user()->isRelevantCentre($this->centre)) {
                    exit("User cannot access that Centre.\n");
                };

                $bar = $this->output->createProgressBar($this->rows->count());

                $this->rows->each(function (CellCollection $row) use ($bar) {

                    $signup_date = Carbon::createFromFormat('Y-m-d', $row->signup_date)->startOfDay();

                    $registration = new Registration([
                        'consented_on' => $signup_date,
                        'eligibility' => 'other',
                    ]);

                    // Make a new family.
                    $family = new Family();

                    $family->initial_centre_id = $this->centre->id;

                    // Cut the prefix and cast left-padded 0's to an int.
                    $family->centre_sequence = (int) preg_replace(
                        "/". $this->centre->prefix . "/",
                        "",
                        $row->seq_reference
                    );

                    // Make some carers
                    $carer = $row->primary_carer_name;
                    $carers = array_values(
                        array_filter(
                            $row->toArray(),
                            function ($value, $key) {
                                return (strpos($key, "secondary_collector") !== false) &&
                                    ($value !== null);
                            },
                            ARRAY_FILTER_USE_BOTH
                        )
                    );
                    $carerModels = array_map(
                        function ($carer) {
                            return new Carer(['name' => $carer]);
                        },
                        array_merge(
                            (array)$carer,
                            $carers
                        )
                    );

                    // make some kids
                    $children = array_values(
                        array_filter(
                            $row->toArray(),
                            function ($value, $key) {
                                return (strpos($key, "child_") !== false) &&
                                    ($value !== null);
                            },
                            ARRAY_FILTER_USE_BOTH
                        )
                    );
                    $childModels = array_map(
                        function ($child) {
                            $month_of_birth = Carbon::createFromFormat('Y-m-d', $child . '-01')->startOfDay();
                            return new Child([
                                'born' => $month_of_birth->isPast(),
                                'dob' => $month_of_birth->toDateTimeString(),
                            ]);
                        },
                        $children
                    );

                    try {
                        DB::transaction(function () use ($registration, $family, $carerModels, $childModels) {
                            $family->save();
                            $family->carers()->saveMany($carerModels);
                            $family->children()->saveMany($childModels);
                            $registration->family()->associate($family);
                            $registration->centre()->associate($this->centre);
                            $registration->save();
                        });
                    } catch (\Exception $e) {
                        // Oops! Log that
                        dd($e->getTraceAsString());
                    }

                    $bar->advance();
                });
                $bar->finish();
                exit("Done.\n");
        }
    }

    public function warnUser()
    {
        $this->info('This unsafe command will alter the database to add records.');
        return $this->confirm('Do you wish to continue?');
    }

}

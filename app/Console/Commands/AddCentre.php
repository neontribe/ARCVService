<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Centre;
use App\Sponsor;
use App\CentreUser;
use Auth;

class AddCentre extends Command
{

    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var Sponsor $sponsor */
    private $sponsor;

    /** @var Centre $centre */
    private $centre;

    /** @var array $preferences */
    private $preferences;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:addCentre
                                {name : the name of the sponsoring entity}
                                {prefix : the prefix that will be part of voucher codes} 
                                {shortcode : the shortcode that id\'s the centre\'s sponsoring entity} 
                                {preference : the form print preference, either "individual" or "collection"}  
                                {email : email address of CentreUser who\'s responsible for this action}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a Children\'s Centre to a sponsoring entity';

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
        // Find centreuser
        $this->centreUser = CentreUser::where('email', $this->argument('email'))
            ->first();

        // Find the pre-exisiting sponsor.
        $this->sponsor = Sponsor::where('shortcode', $this->argument('shortcode'))
            ->first();

        // Check if there's a pre-existing centre.
        $this->centre = Centre::where('prefix', $this->argument('prefix'))
            ->orWhere('name', $this->argument('name'))
            ->first();

        $this->preferences = Centre::select('print_pref')->distinct()->pluck('print_pref')->toArray();

        switch (true) {
            case (!$this->centreUser):
                $this->error("Can't find that User.");
                return 1;
            case (!$this->sponsor):
                $this->error("Sponsor " .
                    $this->argument('shortcode') .
                    " does not exist, exit without change.\n");
                return 2;
            case ($this->centre):
                $this->error("Centre " .
                    $this->centre->name .
                    ", " .
                    $this->centre->prefix .
                    " exists, exit without change.\n");
                return 3;
            case (!in_array($this->argument('preference'), $this->preferences)):
                $this->error("The preference " .
                    $this->argument('preference') .
                    " does not exist, exit without change.\n");
                return 4;
            default:
                // Check the centreuser is happy to proceed
                if (!$this->warnUser()) {
                    $this->error("Exit without change.\n");
                    return 5;
                };
                // Log the centreuser in.
                Auth::login($this->centreUser);

                // Did that work?
                if (!Auth::check()) {
                    $this->error("Failed to login.\n");
                    return 6;
                };

                $this->centre = new Centre([
                    'name' => $this->argument('name'),
                    'prefix' => $this->argument('prefix'),
                    'print_pref' => $this->argument('preference'),
                ]);

                $this->centre->sponsor()->associate($this->sponsor);
                $this->centre->save();

                $this->info("Done.\n");
        }
    }

    public function warnUser()
    {
        $this->info('This unsafe command will alter the database to add records.');
        return $this->confirm('Do you wish to continue?');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Sponsor;
use \App\CentreUser;
use Auth;

class AddSponsor extends Command
{
    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var Sponsor $sponsor */
    private $sponsor;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:addSponsor
                                {name : the name of the sponsoring entity}
                                {shortcode : the prefix that will be part of voucher codes}  
                                {email : email address of User who\'s responsible for this action}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a sponsoring entity to the available sponsors';

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
        $this->centreUser = CentreUser::where('email', $this->argument('email'))->first();

        // Check if there's a Pre-exisiting sponsor.
        $this->sponsor = Sponsor::where('name', $this->argument('name'))
            ->orWhere('shortcode', $this->argument('shortcode'))
            ->first();

        switch (true) {
            case (!$this->centreUser):
                $this->error("Can't find that User.\n");
                return 1;
            case ($this->sponsor):
                $this->error("Sponsor " .
                    $this->sponsor->name .
                    ", " .
                    $this->sponsor->shortcode .
                    " exists, exit without change.\n");
                return 2;

            default:
                // Check the centreuser is happy to proceed
                if (!$this->warnUser()) {
                    $this->error("Exit without change.\n");
                    return 3;
                };
                // Log the centreuser in.
                Auth::login($this->centreUser);

                // Did that work?
                if (!Auth::check()) {
                    $this->error("Failed to login.\n");
                    return 4;
                };

                $this->sponsor = new Sponsor([
                   'name' => $this->argument('name'),
                   'shortcode' => $this->argument('shortcode')
                ]);

                $this->sponsor->save();

                $this->info("Done.\n");
        }
    }

    public function warnUser()
    {
        $this->info('This unsafe command will alter the database to add records.');
        return $this->confirm('Do you wish to continue?');
    }

}

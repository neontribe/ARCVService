<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Auth;
use \App\Sponsor;
use \App\AdminUser;
use \App\Voucher;

class LegacyImport extends Command
{

    private $codes = [];
    private $authUser;
    private $sponsor;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:legacyimport 
                                {vouchers : file of voucher codes to import, one per line.}
                                {shortcode : the sponsoring entity\'s shortcode (eg RVNT).}  
                                {email : email address of AdminUser to act for.}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports voucher codes from file and progresses to "printed"';

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
        // get our codes, user and sponsor.
        $this->codes = file($this->argument('vouchers'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->authUser = AdminUser::where('email', $this->argument('email'))->first();
        $this->sponsor = Sponsor::where('shortcode', $this->argument('shortcode'))->first();

        switch (true) {
            case (!$this->authUser):
                $this->error("Can't find the AdminUser");
                return 1;
            case (!$this->sponsor):
                $this->error("Can't find the Sponsor");
                return 2;
            case (empty($this->codes)):
                $this->error("There are no codes");
                return 3;
            default:
                if (!$this->warnUser()) {
                    $this->error("Failed to log in\n");
                    return 4;
                };
                Auth::login($this->authUser);
                $bar = $this->output->createProgressBar(count($this->codes));
                foreach ($this->codes as $index => $code) {
                    $V = new Voucher;
                    $V->currentstate = 'requested';
                    $V->code = $code;
                    $V->sponsor_id = $this->sponsor->id;
                    $V->save();
                    $V->applyTransition('order');
                    $V->applyTransition('print');
                    $bar->advance();
                }
                $bar->finish();
        }
    }

    /**
     * Warn the user before they execute.
     *
     * @return bool
     */

    public function warnUser()
    {
        $this->info('This unsafe command will alter the database to add records.');
        return $this->confirm('Do you wish to continue?');
    }
}

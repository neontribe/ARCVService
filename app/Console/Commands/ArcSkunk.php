<?php

namespace App\Console\Commands;

use App\Centre;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ArcSkunk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:skunk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $teviot_centre_id = 101;
        $teviot_btg_id = 113;

        $c = Centre::where("id", $teviot_centre_id)->first();
        $reg_associated_with_fam = $c
            ->registrations()
            ->withFullFamily()
            ->get()
            ->filter(function ($f) {
                return in_array($f->family->centre_sequence, [4, 42, 44, 46, 39, 40, 41, 45]);
            });
        $bundles = $reg_associated_with_fam->first()->bundles;
        // "change disbursing_centre_id to new centre id where it is not null";
        foreach ($bundles as $bundle) {
            if ($bundle->disbursing_centre_id == null) {
                $bundle->disbursing_centre_id = $teviot_btg_id;
            }
        }
        // "then there is a method to lock the family to a centre, on the family object, pass in a centre object, true is 2nd param to force";
        $centre = Centre::find($teviot_btg_id);
        foreach ($reg_associated_with_fam as $reg) {
            $reg->family->lockToCentre($centre, true);
        }
        return CommandAlias::SUCCESS;
    }
}

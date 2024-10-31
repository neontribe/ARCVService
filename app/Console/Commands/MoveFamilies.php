<?php

namespace App\Console\Commands;

use App\Centre;
use App\Family;
use App\Registration;
use App\Voucher;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

// docker compose -f .docker/docker-compose.yml exec service /opt/project/artisan arc:skunk
class MoveFamilies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:skunk {from_centre} {to_centre} {family_ids} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move families between centres, e.g. ./artisan arc:skunk 103 101 4,42,44,46,39,40,41,45';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->fixFamalams();
        return CommandAlias::SUCCESS;
    }

    public function fixFamalams()
    {
        // TODO: If we get another request like this we should abstract this to a command that takes these as args
        $from_centre_id = $this->argument('from_centre'); // 101;
        $to_centre_id = $this->argument('to_centre'); // 113;
        $family_list = explode(",", $this->argument('family_ids'));

        $this->info(sprintf("Moving families %s for centre %d to %d", implode(",", $family_list), $from_centre_id, $to_centre_id));

        $from_centre = Centre::where("id", $from_centre_id)->first();
        $to_centre = Centre::find($to_centre_id);
        $this->info(sprintf("From centre: %s, to centre: %s", $from_centre->name, $to_centre->name));

        $reg_associated_with_fam = $from_centre
            ->registrations()
            ->withFullFamily()
            ->get()
            ->filter(function ($f) use ($family_list) {
                return in_array($f->family->centre_sequence, $family_list);
            });

        foreach ($reg_associated_with_fam as $singleReg) {
            /* @var Registration $singleReg */
            $this->info(sprintf(
                "Registration: id='%s', family id=%s, centre id=%s, bundle count=%d",
                $singleReg->id,
                $singleReg->family->id,
                $singleReg->centre->id,
                count($singleReg->bundles),
            ));
            $bundles = $singleReg->bundles;
            // "change disbursing_centre_id to new centre id where it is not null";
            foreach ($bundles as $bundle) {
                $this->info(sprintf(
                    "  Bundle: id=%s, distributing centre id=%s",
                    $bundle->id,
                    $bundle->disbursing_centre_id,
                ));
                if ($bundle->disbursing_centre_id != null && $bundle->disbursing_centre_id != $from_centre_id) {
                    $bundle->disbursing_centre_id = $to_centre_id;
                    $this->info(sprintf("  Updating bundle->disbursing_centre_id = %s", $to_centre_id));
                    $bundle->save();
                }
            }
            $this->info(sprintf("  Locking family %s to centre %s", $singleReg->family->id, $to_centre->id));
            $singleReg->family->lockToCentre($to_centre, true);
            $singleReg->family->save();
            $_family = Family::where("id", $singleReg->family->id)->first();
            \Log::info($_family);
        }
    }
}

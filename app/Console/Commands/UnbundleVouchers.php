<?php

namespace App\Console\Commands;

use App\Bundle;
use App\Voucher;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

// docker compose -f .docker/docker-compose.yml exec service /opt/project/artisan arc:skunk
class UnbundleVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:unbundle {prefix} {start_id} {end_id} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove vouchers from a bundle, e.g. ./artisan arc:unbundle SK 1109247 1109326';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // grab vouchers
        $vouchers = [];
        for ($i = $this->argument('start_id'); $i <= (intval($this->argument('end_id'))); $i++) {
            $vouchers[] = Voucher::where('code', $this->argument('prefix') . $i)->first();
        }

        // get bundle ids
        $bundle_ids = [];
        foreach ($vouchers as $v) {
            $bundle_ids[] = $v->bundle_id;
        }
        $bundle_ids = array_unique($bundle_ids);

        // warn that we will destroy these bundles, other vouchers in the bundle will be unbundled
        $this->warn(sprintf("This will destroy the bundles with the IDs %s", implode(", ", $bundle_ids)));
        if (!$this->confirm('Are you sure you want to proceed?')) {
            // Code to execute if the user confirms
            $this->info('Run back home then little petal...');
        }

        // foreach voucher, null the bundle id
        foreach ($vouchers as $v) {
            $v->bundle_id = null;
            $this->info('Nulling bundle on ' . $v->code);
            $v->save();
        }

        // foreach bundle delete the bundle
        foreach ($bundle_ids as $bundle_id) {
            $b = Bundle::where('id', $bundle_id);
            $this->info('Deleting bundle ' . $bundle_id);
            $b->delete();
        }
        return CommandAlias::SUCCESS;
    }
}

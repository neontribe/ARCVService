<?php

namespace App\Console\Commands;

use App\Centre;
use App\Registration;
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

        return CommandAlias::SUCCESS;
    }
}

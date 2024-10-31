<?php

namespace App\Console\Commands;

use App\Centre;
use App\Family;
use App\Voucher;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

// docker compose -f .docker/docker-compose.yml exec service /opt/project/artisan arc:skunk
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
    public function handle(): int
    {
        $this->findVoucher();
        return CommandAlias::SUCCESS;
    }

    public function findVoucher()
    {
        for ($i = 1109247; $i < 1109326; $i++) {
            $v =Voucher::where("code", "SK" . $i)->first();
            if (!$v) {
                $this->warn("Voucher SK" . $i . " not found");
                continue;
            }
            if ($v->reimbursedOn) {
                $this->info("Voucher SK" . $i . " Reimbursed on " . $v->reimbursedOn->updated_at);
            } else if ($v->paymentPendedOn) {
                $this->info("Voucher SK" . $i . " Payment pending");
            } else {
                $this->info("Voucher SK" . $i . " still live");
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\TransitionProcessor;
use App\Trader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SweepAndSubmitCollectingCentres extends Command
{
    protected $signature = 'arc:sweep-and-submit';

    protected $description = 'Confirms all recorded vouchers for traders in centre-linked markets';

    public function handle(): void
    {
        $traders = Trader::whereHas('market', static function ($q) {
            return $q->whereNotNull('centre_id');
        })->get();

        Log::info(sprintf('SweepAndSubmit command Found %d traders in internal markets', $traders->count()));

        foreach ($traders as $trader) {
            // get a trader's unsubmitted vouchers
            $vouchers = $trader->vouchers()->where('currentstate', 'recorded')->get();

            if ($vouchers->isEmpty()) {
                Log::debug(sprintf(
                    '[SweepAndSubmit] Trader %d (%s): no recorded vouchers, skipping',
                    $trader->id,
                    $trader->name
                ));
                continue;
            }

            Log::info(sprintf(
                '[SweepAndSubmit] Trader %d (%s): processing %d vouchers',
                $trader->id,
                $trader->name,
                $vouchers->count()
            ));

            $processor = new TransitionProcessor($trader, 'confirm');
            $responses = $processor->handle($vouchers);

            Log::info(sprintf(
                '[SweepAndSubmit] Trader %d (%s): results %s',
                $trader->id,
                $trader->name,
                json_encode($responses)
            ));
        }
    }
}

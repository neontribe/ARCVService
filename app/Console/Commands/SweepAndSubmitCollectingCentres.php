<?php

namespace App\Console\Commands;

use App\Services\TransitionProcessor\TransitionProcessor;
use App\Trader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SweepAndSubmitCollectingCentres extends Command
{
    protected $signature = 'arc:sweep-and-submit';

    protected $description = 'Confirms all recorded vouchers for traders in centre-linked markets';

    public function handle(): void
    {
        $count = Trader::whereHas('market', static function ($q) {
            return $q->whereNotNull('centre_id');
        })->count();

        Log::info(sprintf('SweepAndSubmit command Found %d traders in internal markets', $count));

        Trader::whereHas('market', static function ($q) {
            return $q->whereNotNull('centre_id');
        })->chunk(
            50,
            function ($traders): void {
                foreach ($traders as $trader) {
                    $query = $trader->vouchers()->where('currentstate', 'recorded');
                    $count = $query->count();
                    if ($count === 0) {
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
                        $count
                    ));

                    // Builder passed directly — handle() opens the lazy cursor internally.
                    // No invalid detection needed: the query is scoped, not user-submitted.
                    $processor = new TransitionProcessor(
                        trader: $trader,
                        transition: 'confirm',
                        sendPaymentEmail: false
                    );
                    $response = $processor->handle($query);

                    Log::info(sprintf(
                        '[SweepAndSubmit] Trader %d (%s): results %s',
                        $trader->id,
                        $trader->name,
                        json_encode($response->toArray(), JSON_THROW_ON_ERROR)
                    ));
                }
            }
        );
    }
}

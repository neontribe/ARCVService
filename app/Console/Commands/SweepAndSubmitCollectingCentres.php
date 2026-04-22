<?php

namespace App\Console\Commands;

use App\Services\TransitionProcessor;
use App\Trader;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
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

        // get all the traders in markets with centres
        Trader::whereHas('market', static function ($q) {
            return $q->whereNotNull('centre_id');
        })->chunk(
        // 50 traders at once
            50,
            function (Collection $traders): void {
                foreach ($traders as $trader) {
                    // restrict to vouchers that are recorded only
                    $query = $trader->vouchers()->where('currentstate', 'recorded');

                    // some have no vouchers to confirm for payment
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

                    // instantiate the TransitionProcess in confirm mode.
                    $processor = new TransitionProcessor($trader, 'confirm', sendPaymentEmail: false);

                    $responses = $processor->handle($query);

                    Log::info(sprintf(
                        '[SweepAndSubmit] Trader %d (%s): results %s',
                        $trader->id,
                        $trader->name,
                        json_encode($responses, JSON_THROW_ON_ERROR)
                    ));
                }
            }
        );
    }
}

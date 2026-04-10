<?php

namespace App\Services;

use App\Centre;
use App\Market;
use App\Trader;

class CentreCollectionMarketService
{
    public function ensureTradingMarket(Centre $centre): void
    {
        // is there already one from a prior can_collect toggle?
        if (Market::where('centre_id', $centre->id)->trading()->exists()) {
            return;
        }

        // there are no active markets with traders that are assigned to this centre,
        // better make one ...
        $market = Market::create([
            'name' => $centre->name . ' (Internal)',
            'location' => $centre->name,
            'centre_id' => $centre->id,
            // assign it the centre's sponsor/area
            'sponsor_id' => $centre->sponsor->id,
            // don't need one of these, the payment recommendation will be automated
            'payment_message' => '',
        ]);

        // ... and it's trader
        Trader::create([
            'name' => $centre->name . ' (Internal)',
            'market_id' => $market->id,
        ]);
    }
}

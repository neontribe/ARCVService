<?php

namespace App\Services;

use App\Centre;
use App\Market;
use App\Trader;

class CentreCollectionMarketService
{
    public function ensureMarket(Centre $centre): Market
    {
        // withTrashed guards against the edge case where someone
        // manually soft-deleted the market outside normal flows.
        $market = Market::withTrashed()
            ->where('centre_id', $centre->id)
            ->first();

        if ($market) {
            // Restore if soft-deleted
            if ($market->trashed()) {
                $market->restore();
            }
        } else {
            $market = Market::create([
                'name' => $centre->name . ' Collection',
                'location' => $centre->name,
                'centre_id' => $centre->id,
                'sponsor_id' => $centre->sponsor->id,
                'payment_message' => '',
            ]);

            Trader::create([
                'name' => $centre->name . ' Collection Trader',
                'market_id' => $market->id,
            ]);
        }

        return $market;
    }
}

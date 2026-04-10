<?php

namespace App\Observers;

use App\Centre;
use App\Services\CentreCollectionMarketService;

readonly class CentreObserver
{
    private CentreCollectionMarketService $marketService;

    public function __construct(
        CentreCollectionMarketService $marketService
    ) {
        $this->marketService = $marketService;
    }

    public function created(Centre $centre): void
    {
        if ($centre->can_collect) {
            $this->marketService->ensureMarket($centre);
        }
    }

    public function updating(Centre $centre): void
    {
        if (!$centre->isDirty('can_collect')) {
            return;
        }

        if ($centre->can_collect) {
            $this->marketService->ensureMarket($centre);
        }
    }
}

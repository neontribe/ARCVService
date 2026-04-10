<?php

namespace Tests\Unit\Observers;

use App\Centre;
use App\Observers\CentreObserver;
use App\Services\CentreCollectionMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CentreObserverTest extends TestCase
{
    use RefreshDatabase;

    protected CentreCollectionMarketService $marketService;
    protected CentreObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketService = Mockery::mock(CentreCollectionMarketService::class);
        $this->observer = new CentreObserver($this->marketService);
    }

    public function testCreatedCallsEnsureTradingMarketWhenCentreCanCollect(): void
    {
        $centre = factory(Centre::class)->make(['can_collect' => true]);

        $this->marketService
            ->shouldReceive('ensureTradingMarket')
            ->once()
            ->with($centre);

        $this->observer->created($centre);
    }

    public function testCreatedDoesNotCallEnsureTradingMarketWhenCentreCannotCollect(): void
    {
        $centre = factory(Centre::class)->make(['can_collect' => false]);

        $this->marketService
            ->shouldReceive('ensureTradingMarket')
            ->never();

        $this->observer->created($centre);
    }

    public function testUpdatingCallsEnsureTradingMarketWhenCanCollectFlipsToTrue(): void
    {
        $centre = factory(Centre::class)->create(['can_collect' => false]);
        $centre->can_collect = true;

        $this->marketService
            ->shouldReceive('ensureTradingMarket')
            ->once()
            ->with($centre);

        $this->observer->updating($centre);
    }

    public function testUpdatingDoesNotCallEnsureTradingMarketWhenCanCollectFlipsToFalse(): void
    {
        $centre = factory(Centre::class)->create(['can_collect' => true]);
        $centre->can_collect = false;

        $this->marketService
            ->shouldReceive('ensureTradingMarket')
            ->never();

        $this->observer->updating($centre);
    }

    public function testUpdatingDoesNotCallEnsureTradingMarketWhenCanCollectIsUnchanged(): void
    {
        $centre = factory(Centre::class)->create(['can_collect' => true]);
        $centre->name = 'A different name';

        $this->marketService
            ->shouldReceive('ensureTradingMarket')
            ->never();

        $this->observer->updating($centre);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Centre;
use App\Market;
use App\Services\CentreCollectionMarketService;
use App\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentreCollectionMarketServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CentreCollectionMarketService $service;
    protected Centre $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CentreCollectionMarketService();
        $this->centre = factory(Centre::class)->create(['can_collect' => false]);
    }

    public function testItCreatesAMarketWhenNoneExists(): void
    {
        $this->service->ensureTradingMarket($this->centre);

        $this->assertCount(1, Market::where('centre_id', $this->centre->id)->get());
    }

    public function testItCreatesATraderOnTheNewMarket(): void
    {
        $this->service->ensureTradingMarket($this->centre);

        $market = Market::where('centre_id', $this->centre->id)->first();
        $this->assertCount(1, $market->traders);
    }

    public function testNewMarketHasExpectedAttributes(): void
    {
        $this->service->ensureTradingMarket($this->centre);

        $market = Market::where('centre_id', $this->centre->id)->first();
        $this->assertSame($this->centre->name . ' (Internal)', $market->name);
        $this->assertSame($this->centre->name, $market->location);
        $this->assertSame($this->centre->id, $market->centre_id);
        $this->assertSame($this->centre->sponsor->id, $market->sponsor_id);
        $this->assertSame('', $market->payment_message);
    }

    public function testNewTraderHasExpectedAttributes(): void
    {
        $this->service->ensureTradingMarket($this->centre);

        $market = Market::where('centre_id', $this->centre->id)->first();
        $trader = $market->traders()->first();
        $this->assertSame($this->centre->name . ' (Internal)', $trader->name);
        $this->assertSame($market->id, $trader->market_id);
    }

    public function testItDoesNotCreateAMarketWhenOneTradingMarketExists(): void
    {
        $market = factory(Market::class)->create(['centre_id' => $this->centre->id]);
        factory(Trader::class)->create(['market_id' => $market->id]);

        $this->service->ensureTradingMarket($this->centre);

        $this->assertCount(1, Market::where('centre_id', $this->centre->id)->get());
    }

    public function testItDoesNotCreateAMarketWhenMultipleTradingMarketsExist(): void
    {
        factory(Market::class, 2)->create(['centre_id' => $this->centre->id])
            ->each(function ($market) {
                return factory(Trader::class)->create(['market_id' => $market->id]);
            });

        $this->service->ensureTradingMarket($this->centre);

        $this->assertCount(2, Market::where('centre_id', $this->centre->id)->get());
    }

    public function testItCreatesAMarketWhenOnlyNonTradingMarketsExist(): void
    {
        factory(Market::class)->create(['centre_id' => $this->centre->id]);

        $this->service->ensureTradingMarket($this->centre);

        $this->assertCount(2, Market::where('centre_id', $this->centre->id)->get());
    }

    public function testItReturnsVoid(): void
    {
        $result = $this->service->ensureTradingMarket($this->centre);

        $this->assertNull($result);
    }
}

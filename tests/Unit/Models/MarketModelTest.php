<?php

namespace Tests\Unit\Models;

use App\Centre;
use App\Market;
use App\Sponsor;
use App\Trader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketModelTest extends TestCase
{
    use RefreshDatabase;

    protected Market $market;
    protected Sponsor $sponsor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->market = factory(Market::class)->create();
        $this->sponsor = $this->market->sponsor;
    }

    public function testMarketIsCreatedWithExpectedAttributes(): void
    {
        $m = $this->market;
        $this->assertInstanceOf(Market::class, $m);
        $this->assertNotNull($m->name);
        $this->assertNotNull($m->location);
        $this->assertNotNull($m->payment_message);
        $this->assertNotNull($m->sponsor_shortcode);
        $this->assertNotNull($m->sponsor_id);
        $this->assertIsInt($m->sponsor_id);
    }

    public function testMarketBelongsToSponsor(): void
    {
        $this->assertInstanceOf(Sponsor::class, $this->market->sponsor);
    }

    public function testMarketCanHaveManyTraders(): void
    {
        $this->assertInstanceOf(HasMany::class, $this->market->traders());
    }

    public function testGetSponsorShortcodeAttribute(): void
    {
        $this->assertEquals($this->sponsor->shortcode, $this->market->sponsor_shortcode);
    }

    public function testSoftDeleteMarket(): void
    {
        $this->market->delete();
        $this->assertCount(1, Market::withTrashed()->get());
        $this->assertCount(0, Market::all());
    }

    public function testSponsorMarketIsNotInternal(): void
    {
        $this->assertFalse($this->market->isInternal());
    }

    public function testInternalMarketIsInternal(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
            'sponsor_id' => null,
        ]);

        $this->assertTrue($market->isInternal());
    }

    public function testInternalMarketBelongsToCentre(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
            'sponsor_id' => null,
        ]);

        $this->assertInstanceOf(BelongsTo::class, $market->centre());
        $this->assertInstanceOf(Centre::class, $market->centre);
        $this->assertEquals($centre->id, $market->centre->id);
    }

    public function testSponsorMarketHasNullCentre(): void
    {
        $this->assertNull($this->market->centre);
    }

    public function testInternalMarketCanHaveMultipleTraders(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
        ]);

        factory(Trader::class, 2)->create(['market_id' => $market->id]);

        $this->assertCount(2, $market->traders);
    }

    public function testInternalMarketWithNoTradersHasEmptyTradersCollection(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
        ]);

        $this->assertCount(0, $market->traders);
    }

    public function testTradingScopeReturnsMarketsWithTraders(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
        ]);
        factory(Trader::class)->create(['market_id' => $market->id]);

        $this->assertCount(1, Market::trading()->where('centre_id', $centre->id)->get());
    }

    public function testTradingScopeExcludesMarketsWithNoTraders(): void
    {
        $centre = factory(Centre::class)->create();
        factory(Market::class)->create([
            'centre_id' => $centre->id,
        ]);

        $this->assertCount(0, Market::trading()->where('centre_id', $centre->id)->get());
    }

    public function testSoftDeletedInternalMarketIsRestorable(): void
    {
        $centre = factory(Centre::class)->create();
        $market = factory(Market::class)->create([
            'centre_id' => $centre->id,
        ]);

        $market->delete();
        $this->assertNull(Market::find($market->id));

        $market->restore();
        $this->assertNotNull(Market::find($market->id));
    }
}

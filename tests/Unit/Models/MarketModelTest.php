<?php

namespace Tests\Unit\Models;

use App\Market;
use App\Sponsor;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketModelTest extends TestCase
{
    use RefreshDatabase;

    protected $market;
    protected $sponsor;
    protected function setUp(): void
    {
        parent::setUp();
        $this->market = factory(Market::class)->create();
        $this->sponsor = $this->market->sponsor;
    }

    public function testMarketIsCreatedWithExpectedAttributes()
    {
        $m = $this->market;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Market::class, $m);
        $this->assertNotNull($m->name);
        $this->assertNotNull($m->location);
        $this->assertNotNull($m->payment_message);
        $this->assertNotNull($m->sponsor_shortcode);
        $this->assertNotNull($m->sponsor_id);
        $this->assertIsInt($m->sponsor_id);
    }

    public function testMarketBelongsToSponsor()
    {
        $this->assertInstanceOf(BelongsTo::class, $this->market->sponsor());
        $this->assertInstanceOf(Sponsor::class, $this->market->sponsor);
    }

    public function testMarketCanHaveManyTraders()
    {
        $this->assertInstanceOf(HasMany::class, $this->market->traders());
    }

    public function testGetSponsorShortcodeAttribute()
    {
        $shortcode_market = $this->market->sponsor_shortcode;
        $shortcode_sponsor = $this->sponsor->shortcode;
        $this->assertEquals($shortcode_sponsor, $shortcode_market);
    }

    public function testSoftDeleteMarket()
    {
        $this->market->delete();
        $this->assertCount(1, Market::withTrashed()->get());
        $this->assertCount(0, Market::all());
    }
}

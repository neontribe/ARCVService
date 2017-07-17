<?php

namespace Tests\Unit\Models;

use App\Market;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MarketModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $market;
    protected $sponsor;
    protected function setUp()
    {
        parent::setUp();
        $this->market = factory(Market::class)->create();
        $this->sponsor = factory(Sponsor::class)->create([
            'id' => $this->market->sponsor_id = 10,
        ]);
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
        $this->assertInternalType('integer', $m->sponsor_id);
    }

    public function testMarketHasOneSponsor()
    {
        $this->assertInstanceOf(Sponsor::class, $this->market->sponsor);
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

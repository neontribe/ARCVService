<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Market;
use App\Sponsor;
use App\Trader;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class MarketsPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Market $markets */
    private $markets;

    private $marketsRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();

        // Create a single Sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Create 3 markets in an area, with 0, 1 and 2 traders respectively.
        $this->markets = factory(Market::class, 3)
            ->create()
            ->each(
                function ($m, $i) use ($sponsor) {
                    $m->sponsor_id = $sponsor->id;
                    $m->save();
                    factory(Trader::class, $i)->create(['market_id' => $m->id]);
                }
            );

        $this->marketsRoute = route('admin.markets.index');
    }

    /** @test */
    public function itShowsATableWithHeaders()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->marketsRoute)
            ->assertResponseOk()
            ->seeInElement('thead tr th:nth-child(1)', 'Name')
            ->seeInElement('thead tr th:nth-child(2)', 'Area')
            ->seeInElement('thead tr th:nth-child(3)', 'Traders')
            ->seeInElement('thead tr th:nth-child(4)', '');
    }

    /** @test */
    public function itShowsAListWithMarkets()
    {
        $sortedMarkets = $this->markets->sortBy(function ($market) {
            return $market->sponsor->name . '#' .
                $market->name;
        })->values();

        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->marketsRoute)
            ->seeInElement('tbody tr:nth-child(1) td:nth-child(1)', $sortedMarkets[0]->name)
            ->seeInElement('tbody tr:nth-child(2) td:nth-child(1)', $sortedMarkets[1]->name)
            ->seeInElement('tbody tr:nth-child(3) td:nth-child(1)', $sortedMarkets[2]->name);
    }

    /** @test */
    public function eachMarketHasAnAddTraderButton()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->marketsRoute);

        $sortedMarkets = $this->markets->sortBy(function ($market) {
            return $market->sponsor->name . '#' .
                $market->name;
        })->values();

        foreach ($sortedMarkets as $mindex => $market) {
            $this->seeInElement(
                'tbody tr:nth-child(' .
                ($mindex + 1) .
                ') td:nth-child(3)',
                route('admin.traders.create', ['market' => $market->id])
            );
        }
    }

    /** @test */
    public function itShowsCorrectTradersForEachMarket()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->marketsRoute);

        $sortedMarkets = $this->markets->sortBy(function ($market) {
            return $market->sponsor->name . '#' .
                $market->name;
        })->values();

        foreach ($sortedMarkets as $mindex => $market) {
            $market = $market->traders->sortBy('name')->values();
            foreach ($market as $tindex => $trader) {
                $this->seeInElement(
                    'tbody tr:nth-child(' .
                    ($mindex + 1) .
                    ') td:nth-child(3) ul li:nth-child(' .
                    ($tindex + 1) .
                    ')',
                    $trader->name
                );
            }
        }
    }

    /** @test */
    public function itShowsAnEditButtonForEachMarket()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->marketsRoute);

        $markets = $this->markets->sortBy('name')->values();

        foreach ($markets as $mindex => $market) {
            $this->seeInElement(
                'tbody tr:nth-child(' .
                ($mindex + 1) .
                ') td:nth-child(4)',
                '<a href="' . route('admin.markets.edit', ['id' => $market->id]) . '"'
            );
        }
    }
}

<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Sponsor;
use App\Market;
use App\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;

class EditTradersPageTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var string $createRoute */
    private $createRoute;

    /** @var string $storeRoute */
    private $storeRoute;

    /** @var array $postData */
    private $postData;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $sponsor = factory(Sponsor::class)->create();
        // make a market
        $market = factory(Market::class)->create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Test Market',
            'payment_message' => 'Thanks, we got that market'
        ]);
        // trader without users
        $trader = factory(Trader::class)->create([
            'market_id' => $market->id,
            'name' => 'Test Trader',
        ]);

        $this->createRoute = route('admin.traders.edit', ['id' => $trader->id]);
        $this->storeRoute = route('admin.traders.update', ['id' => $trader->id]);

        // template for trader updates
        $this->postData = [
            '_method' => 'PUT',
            'market' => $market->id,
            'name' => 'Test Trader',
        ];
    }

    /** @test */
    public function testItShowsATraderEditPage()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get($this->createRoute)
            ->assertResponseOk()
            ->seePageIs($this->createRoute)
            ->seeInElement('h1', 'Edit Trader')
            ->seeElement('form')
            ->seeInElement('label[for="market"]', 'Market')
            ->seeElement('select[name="market"]')
            ->seeInElement('label[for="name"]', 'Name')
            ->seeElement('input[name="name"]')
            ->seeInElement('label[for="new-user-name"]', 'User Name')
            ->seeElement('input[id="new-user-name"]')
            ->seeInElement('label[for="new-user-email"]', 'User Email')
            ->seeElement('input[id="new-user-email"]')
            ->seeInElement('label[for="disable-toggle"]', 'Disabled')
            ->seeElement('input[type="checkbox"][id="disable-toggle"]')
            ->seeInElement('button[class="updateTrader"]', 'Update All')
            ->dontSeeElement('label[role="alert"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadMarket()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->createRoute)
            ->assertResponseOk()
        ;

        $postData = array_merge(
            $this->postData,
            [
                'market' => 999,
                '_token' => session('_token'),
            ]
        );

        $this->post($this->storeRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->createRoute)
            ->seeElement('label[role="alert"][for="market"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadTraderName()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->createRoute)
            ->assertResponseOk()
        ;

        // set an impossible sponsor
        $postData = array_merge(
            $this->postData,
            [
                'name' => '',
                '_token' => session('_token'),
            ]
        );

        // see an error box for it
        $this->post($this->storeRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->createRoute)
            ->seeElement('label[role="alert"][for="name"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadDisabledCheckbox()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->createRoute)
            ->assertResponseOk()
        ;

        $postData = array_merge(
            $this->postData,
            [
                'disabled' => 'Not a bool-a-like value',
                '_token' => session('_token'),
            ]
        );

        $this->post($this->storeRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->createRoute)
            ->seeElement('label[role="alert"][for="disabled"]')
        ;
    }
}

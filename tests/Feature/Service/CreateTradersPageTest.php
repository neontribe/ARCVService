<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Sponsor;
use App\Market;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CreateTradersPageTest extends StoreTestCase
{
    use DatabaseMigrations;

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
        $this->createRoute = route('admin.traders.create');
        $this->storeRoute = route('admin.traders.store');

        // template for trader
        $this->postData = [
            'market' => $market->id,
            'name' => 'Test Trader'
        ];
    }

    /** @test */
    public function testItShowsATraderCreatePage()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get($this->createRoute)
            ->assertResponseOk()
            ->seePageIs($this->createRoute)
            ->seeInElement('h1', 'Add Trader')
            ->seeElement('form')
            ->seeInElement('label[for="market"]', 'Market')
            ->seeElement('select[name="market"]')
            ->seeInElement('label[for="name"]', 'Name')
            ->seeElement('input[name="name"]')
            ->seeInElement('label[for="new-user-name"]', 'User Name')
            ->seeElement('input[id="new-user-name"]')
            ->seeInElement('label[for="new-user-email"]', 'User Email')
            ->seeElement('input[id="new-user-email"]')
            ->seeInElement('button[class="updateTrader"]', 'Save Trader')
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
                // shouldn't exist
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

        // set an impossible Trader name
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
}

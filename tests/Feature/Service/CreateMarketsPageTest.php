<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CreateMarketsPageTest extends StoreTestCase
{

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
        $this->createRoute = route('admin.markets.create');
        $this->storeRoute = route('admin.markets.store');

        $this->postData = [
            'sponsor' => $sponsor->id,
            'name' => 'Test Market',
            'payment_message' => 'Thanks, we got that market',
        ];
    }

    /** @test */
    public function testItShowsAMarketCreatePage()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get($this->createRoute)
            ->assertResponseOk()
            ->seePageIs($this->createRoute)
            ->seeInElement('h1', 'Add Market')
            ->seeElement('form')
            ->seeInElement('label[for="sponsor"]', 'Area')
            ->seeElement('select[name="sponsor"]')
            ->seeInElement('label[for="name"]', 'Name')
            ->seeElement('input[name="name"]')
            ->seeInElement('label[for="payment_message"]', 'Voucher return message')
            ->seeElement('textarea[name="payment_message"]')
            ->seeInElement('button[id="updateMarket"]', 'Save Market')
            ->dontSeeElement('label[role="alert"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadPaymentMessage()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->createRoute)
            ->assertResponseOk()
        ;

        $postData = array_merge(
            $this->postData,
            [
                'payment_message' => '',
                '_token' => session('_token'),
            ]
        );

        $this->post($this->storeRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->createRoute)
            ->seeElement('label[role="alert"][for="payment_message"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadSponsor()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->createRoute)
            ->assertResponseOk()
        ;

        // set an impossible sponsor
        $postData = array_merge(
            $this->postData,
            [
                'sponsor' => 2,
                '_token' => session('_token'),
            ]
        );

        // see an error box for it
        $this->post($this->storeRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->createRoute)
            ->seeElement('label[role="alert"][for="sponsor"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadMarket()
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
}

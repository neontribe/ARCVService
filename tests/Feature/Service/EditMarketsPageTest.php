<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Market;
use App\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;

class EditMarketsPageTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var string $editRoute */
    private $editRoute;

    /** @var string $updateRoute */
    private $updateRoute;

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
        $this->editRoute = route('admin.markets.edit', ['id' => $market->id]);
        $this->updateRoute = route('admin.markets.update', ['id' => $market->id]);

        // set the template to edit that.
        $this->postData = [
            '_method' => 'put',
            'sponsor' => $sponsor->id,
            'name' => 'Test Market',
            'payment_message' => 'Thanks, we got that market',
        ];
    }

    /** @test */
    public function testItShowsAMarketEditPage()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get($this->editRoute)
            ->assertResponseOk()
            ->seePageIs($this->editRoute)
            ->seeInElement('h1', 'Edit Market')
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
            ->visit($this->editRoute)
            ->assertResponseOk()
        ;

        $postData = array_merge(
            $this->postData,
            [
                'payment_message' => '',
                '_token' => session('_token'),
            ]
        );

        $this->post($this->updateRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->editRoute)
            ->seeElement('label[role="alert"][for="payment_message"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadSponsor()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->editRoute)
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
        $this->post($this->updateRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->editRoute)
            ->seeElement('label[role="alert"][for="sponsor"]')
        ;
    }

    /** @test */
    public function testItShowsAnErrorForBadMarket()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->editRoute)
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
        $this->post($this->updateRoute, $postData)
            ->followRedirects()
            ->seePageIs($this->editRoute)
            ->seeElement('label[role="alert"][for="name"]')
        ;
    }
}

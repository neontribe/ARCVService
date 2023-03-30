<?php

namespace Tests\Unit\Routes;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Market;
use App\Sponsor;
use App\Trader;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class ServiceRoutesTest extends StoreTestCase
{
    use DatabaseMigrations;

    private $authAdminRoutes = [
        'GET' => [
            'admin.dashboard' => [],
            'admin.vouchers.index' => [],
            'admin.vouchers.create' => [],
            'admin.centreusers.index' => [],
            'admin.centreusers.create' => [],
            'admin.centreusers.edit' => ['id' => 1],
            /**
             * * this route streams a csv file and that breaks the test as it tries to modify headers
             * * further thinking needed and maybe a separate testing framework
             */
            // 'admin.centreusers.download' => [],
            'admin.deliveries.create' => [],
            'admin.deliveries.index' => [],
            'admin.centres.index' => [],
            'admin.centres.create' => [],
            'admin.centre_neighbours.index' => ['id' => 1],
            'admin.sponsors.index' => [],
            'admin.sponsors.create' => [],
            'admin.markets.index' => [],
            'admin.markets.create' => [],
            'admin.markets.edit' => ['id' => 1],
            'admin.traders.index' => [],
            'admin.traders.create' => [],
            'admin.traders.edit' => ['id' => 1],
            'admin.payments.index' => [],
            'admin.payment-request.show' => ['paymentUuid' => 1 ],
            'admin.trader-payment-history.show' => ['trader' => 1],
        ],
        'POST' => [
            'admin.vouchers.storebatch' => [],
            'admin.centreusers.store' => [],
            'admin.centres.store' => [],
            'admin.deliveries.store' => [],
            'admin.markets.store' => [],
            'admin.traders.store' => [],
        ],
        'PUT' => [
            'admin.centreusers.update' => ['id' => 1],
            'admin.markets.update' => ['id' => 1],
            'admin.traders.update' => ['id' => 1],
            'admin.payment-request.update' => ['paymentUuid' => 1],
        ],
    ];

    private $adminUser;
    private $centreUser;
    private $sponsor;
    private $market;
    private $trader;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $centres = factory(Centre::class, 3)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        // Map centres on.
        $this->centreUser->centres()->sync([$centres->shift()->id => ['homeCentre' => true]]);
        $this->centreUser->centres()->sync($centres->pluck('id')->all(), false);

        // Add a sponsor, market and a trader
        $this->sponsor = factory(Sponsor::class)->create();
        $this->market = factory(Market::class)->create(['sponsor_id' => $this->sponsor->id]);
        $this->trader = factory(Trader::class)->create(['market_id' => $this->market->id]);
    }

    /** @test */
    public function testServiceLogoutRoute()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.logout'))
            ->followRedirects()
            ->seeRouteIs('admin.login');
    }

    /** @test */
    public function testServiceLoginPageRoute()
    {
        $this->get(route('admin.login'))
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testRouteGates()
    {
        $loginRoute = route('admin.login');

        foreach ($this->authAdminRoutes as $method => $routes) {
            foreach ($routes as $route => $params) {
                // Check an unauthenticated user can't get there
                Auth::logout();
                $response = $this
                    ->makeRequest($method, route($route, $params))
                    ->followRedirects()
                    ->response;
                // Expecting 403 or return to "/login"
                $this->assertTrue(
                    $response->isForbidden()
                    || $this->currentUri === $loginRoute
                );

                // Check an auth'd user can get there.
                $response = $this->actingAs($this->adminUser, 'admin')
                    ->followRedirects()
                    ->call($method, route($route, $params));

                // And it's not 403, 404, 500, or a redirect-to-login.
                $this->assertFalse($response->isNotFound());
                $this->assertFalse($response->isForbidden());
                $this->assertFalse($this->currentUri === $loginRoute);
                $this->assertFalse($response->isServerError());
                $this->assertTrue(
                    $response->isOK()
                    // Posts without data could be 422s...
                    || $response->isClientError()
                    // Response could be a redirect.
                    || $response->isRedirection()
                );
            }
        }
    }
}

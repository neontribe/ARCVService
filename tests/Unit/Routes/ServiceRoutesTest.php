<?php

namespace Tests\Unit\Routes;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Market;
use App\Sponsor;
use App\StateToken;
use App\Trader;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;

class ServiceRoutesTest extends StoreTestCase
{
    use RefreshDatabase;

    /**
     * Routes that require an integer id (model id) use id = 1 because setUp
     * always creates those models first, making id = 1 deterministic.
     *
     * Payment-request routes use a UUID rather than an integer — the UUID is
     * not known until setUp runs, so those entries are overridden in setUp
     * after the StateToken is created. The placeholder value here is never
     * used at runtime.
     */
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
            'admin.centre_neighbours.index' => ['centre' => 1],
            'admin.sponsors.index' => [],
            'admin.sponsors.create' => [],
            'admin.markets.index' => [],
            'admin.markets.create' => [],
            'admin.markets.edit' => ['id' => 1],
            'admin.traders.index' => [],
            'admin.traders.create' => [],
            'admin.traders.edit' => ['id' => 1],
            'admin.payments.index' => [],
            'admin.payment-request.show' => ['paymentUuid' => null],  // overridden in setUp
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
            'admin.payment-request.update' => ['paymentUuid' => null],  // overridden in setUp
        ],
    ];

    private $adminUser;
    private $centreUser;
    private $sponsor;
    private $market;
    private $trader;
    private StateToken $stateToken;

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

        // Payment-request routes require a real StateToken UUID. show() and
        // update() both call firstOrFail(), so passing a non-existent value
        // would return 404 and fail the gate assertion.
        $this->stateToken = factory(StateToken::class)->create();
        $this->authAdminRoutes['GET']['admin.payment-request.show'] = ['paymentUuid' => $this->stateToken->uuid];
        $this->authAdminRoutes['PUT']['admin.payment-request.update'] = ['paymentUuid' => $this->stateToken->uuid];
    }


    public function testServiceLogoutRoute(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.logout'))
            ->followRedirects()
            ->seeRouteIs('admin.login');
    }


    public function testServiceLoginPageRoute(): void
    {
        $this->get(route('admin.login'))
            ->assertResponseStatus(200);
    }


    public function testRouteGates(): void
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
                $this->assertNotSame($this->currentUri, $loginRoute);
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

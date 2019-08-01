<?php

namespace Tests\Unit\Routes;

use App\AdminUser;
use App\Centre;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
// Base on this for laravel browser kit testing.
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
            'admin.centres.index' => [],
            'admin.centre_neighbors.index' => ['id' => 1],
            'admin.sponsors.index' => []
        ],
        'POST' => [
            'admin.vouchers.storebatch' => [],
            'admin.centreusers.store' => [],
        ],
    ];

    private $adminUser;
    private $centre;

    public function setUp()
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create();
    }

    /** @test */
    public function testServiceLogoutRoute()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.logout'))
            ->followRedirects()
            ->seeRouteIs('admin.login')
        ;
    }

    /** @test */
    public function testServiceLoginPageRoute()
    {
        $this->get(route('admin.login'))
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testRouteGates()
    {
        $loginRoute = route('admin.login');

        foreach ($this->authAdminRoutes as $method => $routes) {
            foreach ($routes as $route => $params) {
                // Check an unauth'd user can't get there
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
                    ->call($method, route($route, $params))
                ;
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

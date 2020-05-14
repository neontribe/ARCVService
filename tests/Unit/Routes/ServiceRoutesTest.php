<?php

namespace Tests\Unit\Routes;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
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
            'admin.centreusers.edit' => ['id' => 1],
            'admin.centreusers.download' => [],
            'admin.deliveries.create' => [],
            'admin.deliveries.index' => [],
            'admin.centres.index' => [],
            'admin.centres.create' => [],
            'admin.centre_neighbours.index' => ['id' => 1],
            'admin.sponsors.index' => [],
            'admin.sponsors.create' => [],
        ],
        'POST' => [
            'admin.vouchers.storebatch' => [],
            'admin.centreusers.store' => [],
            'admin.centres.store' => [],
            'admin.deliveries.store' => [],
        ],
        'PUT' => [
            'admin.centreusers.update' => ['id' => 1],
        ]
    ];

    private $adminUser;
    private $centreUser;

    public function setUp()
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $centres = factory(Centre::class, 3)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        // Map centres on.
        $this->centreUser->centres()->sync([$centres->shift()->id => ['homeCentre' => true]]);
        $this->centreUser->centres()->sync($centres->pluck('id')->all(), false);
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

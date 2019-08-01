<?php

namespace Tests\Unit\Routes;

use Config;
use App\AdminUser;
use App\CentreUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ServiceRoutesTest extends TestCase
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
    private $centreUser;

    public function setUp()
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->centreUser = factory(CentreUser::class)->create();
    }

    /** @test */
    public function testServiceLogoutRoute()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect('/')
        ;
    }

    /** @test */
    public function testServiceLoginPageRoute()
    {
        $this->get(route('admin.login'))
            ->assertStatus(200)
        ;
    }

    /** @test */
    public function testRouteGates()
    {
        foreach ($this->authAdminRoutes as $method => $routes) {
            foreach ($routes as $route => $params) {
                $response = $this->actingAs($this->adminUser)
                    ->call($method, route($route, $params))
                ;
                $this->assertFalse($response->isNotFound());
                $this->assertFalse($response->isForbidden());
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

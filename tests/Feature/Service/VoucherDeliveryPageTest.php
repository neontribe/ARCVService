<?php

namespace Tests\Feature\Service;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\AdminUser;
use Auth;

class VoucherDeliveryPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    private $voucherDeliveryRoute;

    public function setUp()
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherDeliveryRoute = route('admin.vouchers.create');
    }

    /**
     * @test
     *
     * @return void
     */
    public function testItCanRouteToVoucherDeliveryPage()
    {
        Auth::logout();
        $this->actingAs($this->adminUser, 'admin')
            ->get($this->voucherDeliveryRoute)
            ->assertResponseOk()
            ->seePageIs($this->voucherDeliveryRoute)
        ;
    }

    /**
     * @test
     *
     * @return void
     */
    public function testRouteIsProtectedFromUnauthorisedUsers()
    {
        Auth::logout();
        $this->get($this->voucherDeliveryRoute)
            ->followRedirects()
            ->seePageIs(route('admin.login'))
            ->assertResponseOk()
        ;
    }
}

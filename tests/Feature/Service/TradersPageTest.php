<?php

namespace Tests\Feature\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\AdminUser;
use Tests\StoreTestCase;

class TradersPageTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var AdminUser */
    private $adminUser;

    private $tradersRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();

        $this->tradersRoute = route('admin.traders.index');
    }

    /**
     * @return void
     */
    public function testItShowsATableWithHeaders(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->tradersRoute)
            ->assertResponseOk()
            ->seeInElement('h1', 'Traders')
            ->seeInElement('th', 'Name')
            ->seeInElement('th', 'Market')
            ->seeInElement('th', 'Area')
            ->seeInElement('th', 'Users')
            ->seeInElement('th', 'Payments')
        ;
    }

    /**
     * @return void
     */
    public function testItShowsADownloadTradersListButton(): void
    {
        $this->actingAs($this->adminUser, 'admin')
        ->visit($this->tradersRoute)
        ->assertResponseOk()
        ->seeInElement('a', 'Download Trader List')
        ;
    }
}

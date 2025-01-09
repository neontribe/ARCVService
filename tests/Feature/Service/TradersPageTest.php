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
     * @test
     *
     * @return void
     */
    public function itShowsATableWithHeaders()
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
     * @test
     * @return void
     */
    public function itShowsADownloadTradersListButton()
    {
        $this->actingAs($this->adminUser, 'admin')
        ->visit($this->tradersRoute)
        ->assertResponseOk()
        ->seeInElement('a', 'Download Trader List')
      ;
    }
}

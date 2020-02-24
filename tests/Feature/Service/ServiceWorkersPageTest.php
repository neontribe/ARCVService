<?php

namespace Tests\Feature\Service;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\AdminUser;


class ServiceWorkersPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;
    private $workersRoute;

    public function setUp()
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->workersRoute = route('admin.centreusers.index');
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShowsAListOfWorkers()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->workersRoute)
            ->assertResponseOk()
            ->seeInElement('h1', 'Workers')
            ->seeInElement('th', 'Name')
            ->seeInElement('th', 'Email Address')
            ->seeInElement('th', 'Home Centre')
            ->seeInElement('th', 'Alternative Centres')
            ->seeInElement('th', 'Edit')
            ->seeInElement('th', 'Downloader')
            ->see('Downloader')
        ;
    }
}

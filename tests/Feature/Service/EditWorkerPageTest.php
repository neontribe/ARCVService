<?php

namespace Tests\Feature\Service;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\AdminUser;
use App\Centre;
use App\CentreUser;
use Illuminate\Support\Collection;

class EditWorkerPageTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Centre $centre */
    private $centre;

    /** @var Collection $altCentres */
    private $altCentres;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create([]);
        $this->altCentres = factory(Centre::class, 2)->create([]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShowsAWorkerEditPage()
    {
        // Make a CentreUser from the data with 1 homeCentre.
        $cu = factory(CentreUser::class)->create([
            'name' => 'testman',
            'email' => 'testman@test.co.uk',
        ]);
        $cu->centres()->attach($this->altCentres->last()->id, ['homeCentre' => true]);
        $workerEditRoute = route('admin.centreusers.edit', ['id' => $cu->id,]);

        $this->actingAs($this->adminUser, 'admin')
            ->get($workerEditRoute)
            ->assertResponseOk()
            ->seePageIs($workerEditRoute)
            ->seeInElement('h1', 'Edit a Children\'s Centre Worker')
            ->seeElement('form')
            ->seeInElement('label[for="name"]', 'Name')
            ->seeInElement('label[for="email"]', 'Email Address')
            ->seeElement('label[for="worker_centre"]')
            ->seeInElement('label[for="worker_centre"]', 'Home Centre')
            ->seeElement('select[name="worker_centre"]')
            ->seeElement('label[for="downloader"]')
            ->seeInElement('label[for="downloader"]', 'Downloader Status')
            ->seeElement('select[name="downloader"]')
        ;
    }
}

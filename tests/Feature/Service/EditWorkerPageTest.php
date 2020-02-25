<?php

namespace Tests\Feature\Service;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\AdminUser;
use App\Centre;
use App\CentreUser;
use Illuminate\Support\Collection;

class EditWorkerPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Centre $centre */
    private $centre;

    /** @var Collection $altCentres */
    private $altCentres;

    /** @var array $data */
    private $data;

    public function setUp()
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create([]);
        $this->altCentres = factory(Centre::class, 2)->create([]);
        $this->data = [
            'name' => 'bobby testee',
            'email' => 'bobby@test.co.uk',
            'worker_centre' => $this->centre->id,
            'alternative_centres.*' => $this->altCentres->pluck('id')->all(),
            'downloader' => 1,
        ];
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

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $cu->id,]), $this->data)
            ->assertResponseOk()
            ->seeInElement('h1', 'Edit a Children\'s Centre Worker')
            ->seeElement('form')
            ->seeInElement('label[for="name"]', 'Name')
            ->seeInElement('label[for="email"]', 'Email Address')
            ->seeElement('label[for="worker_centre"]')
            ->seeElement('select[name="worker_centre"]')
            ->seeElement('label[for="downloader"]')
            ->seeElement('select[name="downloader"]')
        ;
    }
}

<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\StoreTestCase;

class ServiceWorkersPageTest extends StoreTestCase
{
    use RefreshDatabase;

    private AdminUser $adminUser;

    private CentreUser $userHomeCentreNoAlt;

    private CentreUser $userHomeCentreTwoAlt;

    private CentreUser $downloaderUser;

    private Collection $centres;

    private string $workersRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        // Create a single Sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Create 3 centres in an area
        $this->centres = factory(Centre::class, 3)->create()->each(
            function ($c) use ($sponsor) {
                $c->sponsor_id = $sponsor->id;
                $c->save();
            }
        );

        // Create 2 alt centres
        $altCentres = factory(Centre::class, 2)->create([]);
        $this->workersRoute = route('admin.centreusers.index');

        // Create 1 user with a home centre and no alternatives
        $this->userHomeCentreNoAlt = factory(CentreUser::class)->create([
            "name" => "test user",
            "email" => "testuser@example.com",
        ]);
        $this->userHomeCentreNoAlt->centres()->attach(1, ['homeCentre' => true]);

        // Create 1 user with with a homeCentre and 2 alternatives
        $this->userHomeCentreTwoAlt = factory(CentreUser::class)->create([
            "name" => "test user with alternatives",
            "email" => "testuseralternatives@example.com",
        ]);

        $this->userHomeCentreTwoAlt->centres()->attach(1, ['homeCentre' => true]);
        $this->userHomeCentreTwoAlt->centres()->attach($altCentres->all());

        // Create 1 user with a homeCentre who can Download
        $this->downloaderUser = factory(CentreUser::class)->state('withDownloader')->create([
            "name" => "test downloader",
            "email" => "testdl@example.com",
        ]);
        $this->downloaderUser->centres()->attach(1, ['homeCentre' => true]);
    }

    public function testItShowsATableWithHeaders(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->workersRoute)
            ->assertResponseOk()
            ->seeInElement('h1', 'Workers')
            ->seeInElement('th', 'Name')
            ->seeInElement('th', 'Email Address')
            ->seeInElement('th', 'Home Centre Area')
            ->seeInElement('th', 'Home Centre')
            ->seeInElement('th', 'Alternative Centres')
            ->seeInElement('th', 'Downloader')
            ->see('Downloader');
    }

    public function testItShowsAListWithUsers(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->workersRoute)
            ->seeInElementAtPos('tbody tr', $this->downloaderUser['name'], 0)
            ->seeInElementAtPos('tbody tr td', $this->downloaderUser['email'], 1)
            ->seeInElementAtPos('tbody tr', $this->userHomeCentreNoAlt['name'], 1)
            ->seeInElementAtPos('tbody tr td', $this->userHomeCentreNoAlt['email'], 8)
            ->seeInElementAtPos('tbody tr', $this->userHomeCentreTwoAlt['name'], 2)
            ->seeInElementAtPos('tbody tr td', $this->userHomeCentreTwoAlt['email'], 15);
    }

    public function testItShowsADownloadWorkersListButton(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->workersRoute)
            ->assertResponseOk()
            ->seeInElement('a', 'Download Worker List');
    }
}

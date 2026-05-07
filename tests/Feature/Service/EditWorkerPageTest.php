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

    private AdminUser $adminUser;

    private Centre $centre;

    private Collection $altCentres;

    private CentreUser $worker;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create([]);
        $this->altCentres = factory(Centre::class, 2)->create([]);

        $this->worker = factory(CentreUser::class)->create([
            'name'  => 'testman',
            'email' => 'testman@test.co.uk',
        ]);
        $this->worker->centres()->attach($this->centre->id, ['homeCentre' => true]);
    }

    // -----------------------------------------------------------------------
    // Page structure — active worker
    // -----------------------------------------------------------------------

    public function testItShowsAWorkerEditPage(): void
    {
        $workerEditRoute = route('admin.centreusers.edit', ['id' => $this->worker->id]);

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

    public function testActiveWorkerShowsUpdateButton(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeElement('#updateWorker')
        ;
    }

    public function testActiveWorkerShowsDisableButtonNotEnableButton(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeInElement('#toggleWorker', 'Disable worker')
            ->dontSeeInElement('#toggleWorker', 'Enable worker')
        ;
    }

    public function testActiveWorkerDoesNotShowRetireForm(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->dontSeeElement('#retireForm')
            ->dontSeeElement('#retireWorker')
        ;
    }

    public function testActiveWorkerDoesNotShowDisabledMessage(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->dontSee('This worker is')
        ;
    }

    public function testWorkerHomeCentreIsPreselectedInDropdown(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeElement('option[value="' . $this->centre->id . '"][selected]')
        ;
    }

    public function testWorkerAlternativeCentresAreRenderedAsOptions(): void
    {
        $this->worker->centres()->attach([
            $this->altCentres[0]->id => ['homeCentre' => false],
            $this->altCentres[1]->id => ['homeCentre' => false],
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeElement('option[value="' . $this->altCentres[0]->id . '"]')
            ->seeElement('option[value="' . $this->altCentres[1]->id . '"]')
        ;
    }

    // -----------------------------------------------------------------------
    // Page structure — disabled (soft-deleted) worker
    // -----------------------------------------------------------------------

    public function testDisabledWorkerShowsDisabledMessage(): void
    {
        $this->worker->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeInElement('h2', 'This worker is')
            ->seeInElement('h2 i', 'disabled')
        ;
    }

    public function testDisabledWorkerDoesNotShowUpdateButton(): void
    {
        $this->worker->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->dontSeeElement('#updateWorker')
        ;
    }

    public function testDisabledWorkerShowsEnableButtonNotDisableButton(): void
    {
        $this->worker->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeInElement('#toggleWorker', 'Enable worker')
            ->dontSeeInElement('#toggleWorker', 'Disable worker')
        ;
    }

    public function testDisabledWorkerShowsRetireForm(): void
    {
        $this->worker->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeElement('#retireForm')
            ->seeElement('#retireWorker')
        ;
    }

    public function testDisabledWorkerRetireFormPostsToCorrectRoute(): void
    {
        $this->worker->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseOk()
            ->seeElement(
                '#retireForm[action="' . route('admin.centreusers.retire', ['id' => $this->worker->id]) . '"]'
            )
        ;
    }

    // -----------------------------------------------------------------------
    // Access control
    // -----------------------------------------------------------------------

    public function testUnauthenticatedUserCannotAccessEditPage(): void
    {
        $this->get(route('admin.centreusers.edit', ['id' => $this->worker->id]))
            ->assertResponseStatus(302)
        ;
    }
}

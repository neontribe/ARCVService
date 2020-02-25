<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Tests\StoreTestCase;

class CentreUserControllerTest extends StoreTestCase
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
        $this->altCentres = factory(Centre::class, 3)->create([]);
        $this->data = [
            'name' => 'bobby testee',
            'email' => 'bobby@test.co.uk',
            'worker_centre' => $this->centre->id,
            'alternative_centres.*' => $this->altCentres->pluck('id')->all(),
        ];
    }

    /** @test */
    public function testICanStoreACentreUser()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(
                route('admin.centreusers.store'),
                $this->data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see($this->data["name"])
            ->see($this->data["email"])
        ;
        // find the user
        $cu = CentreUser::where('email', $this->data['email'])->first();
        $this->assertNotNull($cu);
        // Check the neighbours
        $this->assertEquals(4, $cu->relevantCentres()->count());
        // Check the homeCentre
        $this->assertEquals($this->data['worker_centre'], $cu->homeCentre->id);
    }

    /** @test */
    public function testItCanUpdateACentreUser()
    {
        // Make a CentreUser from the data with 1 homeCentre.
        $cu = factory(CentreUser::class)->create([
            'name' => "testman",
            'email' => "testman@test.co.uk",
        ]);
        $cu->centres()->attach($this->altCentres->last()->id, ['homeCentre' => true]);

        $this->seeInDatabase('centre_users', [
            'name' => "testman",
            'email' => "testman@test.co.uk",
        ]);
        // Check that worked.
        $this->assertCount(1, $cu->centres);
        $this->assertEquals($this->altCentres->last()->id, $cu->homeCentre->id);

        // Totally update the name, email and centre config from the $data!
        $this->actingAs($this->adminUser, 'admin')
            ->post(
                route('admin.centreusers.store'),
                $this->data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see($this->data["name"])
            ->see($this->data["email"])
            ->see($this->data['downloader'])
        ;
        ;
        // find the user
        $cu = CentreUser::where('email', $this->data['email'])->first();
        $this->assertNotNull($cu);
        // Check the neighbours
        $this->assertEquals(4, $cu->relevantCentres()->count());
        // Check the homeCentre
        $this->assertEquals($this->data['worker_centre'], $cu->homeCentre->id);
    }
}

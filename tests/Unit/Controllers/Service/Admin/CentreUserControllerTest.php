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

    public function setUp(): void
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
            'downloader' => 0,
        ]);
        $cu->centres()->attach($this->altCentres->last()->id, ['homeCentre' => true]);

        $this->seeInDatabase('centre_users', [
            'name' => $cu->name,
            'email' => $cu->email,
            'downloader' => $cu->downloader
        ]);
        // Check that worked.
        $this->assertCount(1, $cu->centres);
        $this->assertEquals($this->altCentres->last()->id, $cu->homeCentre->id);

        $this->data['downloader'] = 1;

        // Totally update the name, email, centre config and downloader status from the $data!
        $this->actingAs($this->adminUser, 'admin')
            ->put(
                route('admin.centreusers.update', $cu->id),
                $this->data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see($this->data['name'])
            ->see($this->data['email'])
            ->see($this->data['worker_centre'])
            ->see($this->data['downloader'])
            ->seeInDatabase('centre_users', [
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'downloader' => $this->data['downloader']
            ])
            ->dontSeeInDatabase('centre_users', [
                'name' => $cu->name,
                'email' => $cu->email,
            ])
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

    /** @test */
    public function testItCanDeleteACentreUser()
    {
        $cu = factory(CentreUser::class)->create([
            'name' => "testman",
            'email' => "testman@test.co.uk",
        ]);
        $cu->centres()->attach($this->altCentres->last()->id, ['homeCentre' => true]);

        $this->seeInDatabase('centre_users', [
            'name' => $cu->name,
            'email' => $cu->email,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(
                route('admin.centreusers.delete', $cu->id),
                $this->data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see('Worker ' . $cu->name . ' deleted')
            ->dontSee($cu->email)
            ->notSeeInDatabase('centre_users', [
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'deleted_at' => null
            ])
            ->dontSeeInDatabase('centre_centre_user', [
                'centre_user)id' => $cu->id,
                'centre_id' => $this->altCentres->last()->id,
            ])
        ;
    }

    /** @test */
    public function testICannotSeeDeletedCentreUsers()
    {
        $cu = factory(CentreUser::class)->create([
            'name' => "testman",
            'email' => "testman@test.co.uk",
            'deleted_at' => date("Y-m-d H:i:s")
        ]);

        $this->seeInDatabase('centre_users', [
            'name' => $cu->name,
            'email' => $cu->email,
            'deleted_at' => date("Y-m-d H:i:s")
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.centreusers.index'))
            ->assertResponseOk()
            ->dontSee($cu->email)
        ;
    }
}

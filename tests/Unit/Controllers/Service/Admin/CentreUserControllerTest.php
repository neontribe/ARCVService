<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CentreUserControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    private $centre;

    private $altCentres;

    public function setUp()
    {
        parent::setUp();
        $this->centre = factory(Centre::class)->create([]);
        $this->altCentres = factory(Centre::class, 3)->create([]);
    }

    /** @test */
    public function testICanStoreACentreUser()
    {
        $adminUser = factory(AdminUser::class)->create();

        $data = [
            'name' => 'bobby testee',
            'email' => 'bobby@test.co.uk',
            'worker_centre' => $this->centre->id,
            'alternative_centres.*' => $this->altCentres->pluck('id')->all()
        ];

        $this->actingAs($adminUser, 'admin')
            ->post(
                route('admin.centreusers.store'),
                $data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see($data["name"])
            ->see($data["email"])
        ;
        // find the user
        $cu = CentreUser::where('email', $data['email'])->first();
        $this->assertNotNull($cu);
        // Check the neighbours
        $this->assertEquals(4, $cu->relevantCentres()->count());
        // Check the homeCentre
        $this->assertEquals($data['worker_centre'], $cu->homeCentre->id);
    }
}

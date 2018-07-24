<?php

namespace Tests;

use App\Centre;
//use App\Registration;
use App\Sponsor;
use App\CentreUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;

class CentreModelTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function itHasExpectedAttributes()
    {
        $centre = factory(Centre::class)->make();
        $this->assertNotNull($centre->name);
        $this->assertNotNull($centre->sponsor_id);
        $this->assertContains($centre->print_pref, ['collection', 'individual']);
    }

    /** @test */
    public function itHasASponsor()
    {
        $centre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);
        $this->assertInstanceOf(Sponsor::class, $centre->sponsor);
    }

//    /** @test */
//    public function itCanHaveRegistrations()
//    {
//        $centre = factory(Centre::class)->create();
//        $registrations = factory(Registration::class, 3)->create([
//            'centre_id' => $centre->id,
//        ]);
//        $registrations = $centre->registrations;
//        $this->assertInstanceOf(Collection::class, $registrations);
//        $this->assertInstanceOf(Registration::class, $registrations[0]);
//    }

    /** @test */
    public function itCanHaveUsers()
    {
        $centre = factory(Centre::class)->create();
        $centreUsers = factory(CentreUser::class, 3)->create([
            'centre_id' => $centre->id,
        ]);
        $centreUsers = $centre->CentreUsers;
        $this->assertInstanceOf(Collection::class, $centreUsers);
        $this->assertInstanceOf(CentreUser::class, $centreUsers[0]);
    }

    /** @test */
    public function itCanHaveNeighbors()
    {
        $sponsor_a = factory(Sponsor::class)->create();
        $sponsor_b = factory(Sponsor::class)->create();

        $a_centres = factory(Centre::class, 2)->create([
            'sponsor_id' => $sponsor_a->id,
        ]);
        $b_centres = factory(Centre::class, 3)->create([
            'sponsor_id' => $sponsor_b->id,
        ]);

        // The ids of centre collections a and b are not the same.
        $this->assertNotEquals($a_centres->pluck('id'), $b_centres->pluck('id'));

        // For each centre in both collections, they know thine own neighbors...
        foreach ($a_centres as $ac) {
            $this->assertCount(2, $ac->neighbors);
            $this->assertEquals($a_centres->pluck('id'), $ac->neighbors->pluck('id'));
        }
        foreach ($b_centres as $bc) {
            $this->assertCount(3, $bc->neighbors);
            $this->assertEquals($b_centres->pluck('id'), $bc->neighbors->pluck('id'));
        }
    }
}

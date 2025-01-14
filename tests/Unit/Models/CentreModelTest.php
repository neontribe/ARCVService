<?php

namespace Tests\Unit\Models;

use App\Centre;
use App\Registration;
use App\Sponsor;
use App\CentreUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CentreModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function itHasExpectedAttributes()
    {
        $centre = factory(Centre::class)->make();
        $this->assertNotNull($centre->name);
        $this->assertNotNull($centre->sponsor_id);
        $this->assertContains($centre->print_pref, config('arc.print_preferences'));
    }

    /** @test */
    public function itHasASponsor()
    {
        $centre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);
        $this->assertInstanceOf(Sponsor::class, $centre->sponsor);
    }

    /** @test */
    public function itCanHaveRegistrations()
    {
        $centre = factory(Centre::class)->create();
        factory(Registration::class, 3)->create([
            'centre_id' => $centre->id,
        ]);
        $registrations = $centre->registrations;
        $this->assertInstanceOf(Collection::class, $registrations);
        $this->assertInstanceOf(Registration::class, $registrations[0]);
    }

    /** @test */
    public function itCanHaveNoRegistrations()
    {
        $centre = factory(Centre::class)->create();
        $registrations = $centre->registrations;
        $this->assertInstanceOf(Collection::class, $registrations);
        $this->assertEquals(0, $registrations->count());
    }

    /** @test */
    public function itCanHaveUsers()
    {
        $centre = factory(Centre::class)->create();

        factory(CentreUser::class, 3)
            ->create()
            ->each(
                function (CentreUser $centreUser) use ($centre) {
                    // Technically we should be setting one homeCentre for each user.
                    // Deemed unneccessary for purposes of this test at time of writing.
                    $centreUser->centres()->attach($centre);
                }
            );

        $centreUsers = $centre->centreUsers;
        $this->assertInstanceOf(Collection::class, $centreUsers);
        $this->assertInstanceOf(CentreUser::class, $centreUsers[0]);
    }

    /** @test */
    public function itCanHaveNeighbours()
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

        // For each centre in both collections, they know thine own neighbours...
        foreach ($a_centres as $ac) {
            $this->assertCount(2, $ac->neighbours);
            $this->assertEquals($a_centres->pluck('id'), $ac->neighbours->pluck('id'));
        }
        foreach ($b_centres as $bc) {
            $this->assertCount(3, $bc->neighbours);
            $this->assertEquals($b_centres->pluck('id'), $bc->neighbours->pluck('id'));
        }
    }
}

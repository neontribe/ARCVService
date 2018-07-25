<?php

namespace Tests;

use App\Carer;
use App\Family;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CarerModelTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function itHasExpectedAttributes()
    {
        $carer = factory(Carer::class)->make();
        $this->assertNotNull($carer->name);
    }

    /** @test */
    public function itCanHaveAFamily()
    {
        // Make a Family
        $family = factory(Family::class)->create();
        // Add a Carer
        $carer = factory(Carer::class)->make();
        $family->carers()->save($carer);

        // Check that the carer family relationship works
        $this->assertNotNull($carer->family);
        $this->assertEquals($carer->family->id, $carer->family_id);
    }
}
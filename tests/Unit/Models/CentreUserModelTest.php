<?php

namespace Tests\Unit\Models;

use App\Centre;
use App\CentreUser;
use App\Note;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CentreUserModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $centreUser;
    protected $notes;
    protected function setUp()
    {
        parent::setUp();
        $this->centreUser = factory(CentreUser::class)->create()->fresh();
        $this->notes = factory(Note::class, 2)->create(['user_id' => $this->centreUser->id]);
    }

    /** @test */
    public function testCentreUserHasExpectedAttributes()
    {
        $cu = $this->centreUser;
        $this->assertNotNull($cu->name);
        $this->assertNotNull($cu->email);
        $this->assertContains($cu->role, ['centre_user', 'foodmatters_user']);
        // Default false
        $this->assertFalse($cu->downloader);
    }

    /** @test */
    public function testCentreUserCanHaveNotes()
    {
        $this->assertCount(2, $this->centreUser->notes);
    }

    /**@test */
    public function testCentreUserCanHaveDownloadTrue()
    {
        // Standard CU
        $cu = $this->centreUser;
        $this->assertFalse($cu->downloader);

        // Change their settings
        $cu->downloader = true;
        $cu->fresh();
        $this->assertTrue($cu->downloader);

        $cu = factory(CentreUser::class, 'withDownloader')->create()->fresh();
        $this->assertTrue($cu->downloader);
    }

    /** @test */
    public function testCentreUserCanHaveAHomeCentre()
    {
        $cu = $this->centreUser;
        // Has no centres;
        $this->assertEmpty($cu->centres);
        $this->assertNull($cu->homeCentre);

        // Make one, set it to Home
        $centre = factory(Centre::class)->create();
        $cu->centres()->attach($centre->id, ['homeCentre' => true]);

        // There is one
        $this->assertEquals(1, $cu->centres()->count());
        // It is the homeCentre
        $this->assertEquals($centre->id, $cu->homeCentre->id);
    }

    /** @test */
    public function testCentreUserCanHaveAlternativeCentres()
    {
        $cu = $this->centreUser;
        // Has no centres;
        $this->assertEmpty($cu->centres);

        // Make some
        $centres = factory(Centre::class, 4)->create();
        $cu->centres()->attach($centres->pluck('id')->all());

        // There is 4
        $this->assertEquals(4, $cu->centres()->count());

        // But We have no homeCentre
        $this->assertEmpty($cu->homeCentre);
    }
}
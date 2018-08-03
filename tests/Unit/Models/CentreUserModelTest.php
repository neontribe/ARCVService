<?php

namespace Tests\Unit\Models;

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
        $this->centreUser = factory(CentreUser::class)->create();
        $this->notes = factory(Note::class, 2)->create(['user_id' => $this->centreUser->id]);

    }

    public function testCentreUserHasExpectedAttributes()
    {
        $cu = $this->centreUser;
        $this->assertNotNull($cu->name);
        $this->assertNotNull($cu->email);
        $this->assertContains($cu->role, ['centre_user', 'foodmatters_user']);
    }

    public function testCentreUserCanHaveNotes()
    {
        $this->assertCount(2, $this->centreUser->notes);
    }

}
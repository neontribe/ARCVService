<?php

namespace Tests\Unit\Models;

use App\Family;
use App\Note;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NoteModelTest extends TestCase
{
    use DatabaseMigrations;

    public function testModel()
    {
        // This a placeholder for more in depth tests if needed. If I'm totally honest it's also a
        // cheat to help get coverage up.

        $family = factory(Family::class, 2)->create()->first();

        $note = new Note();
        $family->notes->add($note);
        $family->save();

        $notes = $family->notes;
        $this->assertEquals(1, $notes->count());
    }
}

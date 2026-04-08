<?php

namespace Tests\Unit\Models;

use App\Centre;
use App\CentreUser;
use App\Note;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentreUserModelTest extends TestCase
{
    use RefreshDatabase;

    protected $centreUser;
    protected $notes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->centreUser = factory(CentreUser::class)->create()->fresh();
        $this->notes = factory(Note::class, 2)->create(['user_id' => $this->centreUser->id]);
    }


    public function testCentreUserHasExpectedAttributes(): void
    {
        $cu = $this->centreUser;
        $this->assertNotNull($cu->name);
        $this->assertNotNull($cu->email);
        $this->assertContains($cu->role, ['centre_user', 'foodmatters_user']);
        // Default false
        $this->assertFalse($cu->downloader);
    }


    public function testCentreUserCanHaveNotes(): void
    {
        $this->assertCount(2, $this->centreUser->notes);
    }

    /** */
    public function testCentreUserCanHaveDownloadTrue(): void
    {
        // Standard CU
        $cu = $this->centreUser;
        $this->assertFalse($cu->downloader);

        // Change their settings
        $cu->downloader = true;
        $cu->fresh();
        $this->assertTrue($cu->downloader);

        $cu = factory(CentreUser::class)->state('withDownloader')->create()->fresh();
        $this->assertTrue($cu->downloader);
    }


    public function testCentreUserCanHaveAHomeCentre(): void
    {
        $cu = $this->centreUser;
        // Has no centres;
        $this->assertEmpty($cu->centres);
        $this->assertNull($cu->homeCentre);

        // Make one, set it to Home
        $centre = factory(Centre::class)->create();
        $cu->centres()->attach($centre->id, ['homeCentre' => true]);

        // There is one
        $this->assertSame(1, $cu->centres()->count());
        // It is the homeCentre
        $this->assertSame($centre->id, $cu->homeCentre->id);
    }


    public function testCentreUserCanHaveAlternativeCentres(): void
    {
        $cu = $this->centreUser;
        // Has no centres;
        $this->assertEmpty($cu->centres);

        // Make some
        $centres = factory(Centre::class, 4)->create();
        $cu->centres()->attach($centres->pluck('id')->all());

        // There is 4
        $this->assertSame(4, $cu->centres()->count());

        // But We have no homeCentre
        $this->assertEmpty($cu->homeCentre);
    }

    // -----------------------------------------------------------------------
    // Retirable — CentreUser-specific tests
    //
    // These tests cover the concrete PII replacement values declared in
    // CentreUser::retirableFields() and the survival of CentreUser's own
    // relations after retirement. Generic trait behaviour (idempotency,
    // soft-delete, scopes, boot guard) is covered in RetirableTest.
    // -----------------------------------------------------------------------

    public function testCentreUserIsNotRetiredByDefault(): void
    {
        $this->assertFalse($this->centreUser->isRetired());
        $this->assertNull($this->centreUser->retired_at);
    }

    public function testRetiredCentreUserHasNameCleared(): void
    {
        $this->centreUser->retire();

        $fresh = CentreUser::withTrashed()->find($this->centreUser->id);
        $this->assertSame('[User Retired]', $fresh->name);
    }

    public function testRetiredCentreUserHasEmailReplacedWithSafeRetiredPlaceholder(): void
    {
        $originalEmail = $this->centreUser->email; // capture before retire() mutates the instance

        $this->centreUser->retire();

        $email = CentreUser::withTrashed()->find($this->centreUser->id)->email;

        // Confirm format matches CentreUser::retirableFields() convention.
        $this->assertStringStartsWith('retired_', $email);
        $this->assertStringEndsWith('@retired.invalid', $email);

        // Confirm the original email is gone.
        $this->assertNotSame($originalEmail, $email);
    }

    public function testRetiredCentreUserHasPasswordReplacedWithANewHash(): void
    {
        $originalPassword = $this->centreUser->password; // capture before retire() mutates the instance

        $this->centreUser->retire();

        $replacedPassword = CentreUser::withTrashed()->find($this->centreUser->id)->password;

        $this->assertTrue(Hash::isHashed($replacedPassword));
        $this->assertNotSame($originalPassword, $replacedPassword);
    }

    public function testRetiredCentreUserHasRememberTokenCleared(): void
    {
        $this->centreUser->retire();

        $fresh = CentreUser::withTrashed()->find($this->centreUser->id);
        $this->assertNull($fresh->remember_token);
    }

    public function testRetiredCentreUserRetainsNoteRelations(): void
    {
        // Notes exist before retirement.
        $this->assertCount(2, $this->centreUser->notes);

        $this->centreUser->retire();

        // Notes are still associated via FK after retirement.
        $fresh = CentreUser::withTrashed()->find($this->centreUser->id);
        $this->assertCount(2, $fresh->notes);
    }

    public function testRetiredCentreUserRetainsCentreRelations(): void
    {
        $centre = factory(Centre::class)->create();
        $this->centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        $this->centreUser->retire();

        $fresh = CentreUser::withTrashed()->find($this->centreUser->id);
        $this->assertSame(1, $fresh->centres()->count());
    }

    public function testRetiredCentreUserCannotBeRestored(): void
    {
        $cu = factory(CentreUser::class)->state('retired')->create()->fresh();

        $this->expectException(DomainException::class);

        CentreUser::withTrashed()->find($cu->id)->restore();
    }
}

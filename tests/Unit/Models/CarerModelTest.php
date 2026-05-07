<?php

namespace Tests\Unit\Models;

use App\Carer;
use App\Family;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarerModelTest extends TestCase
{
    use RefreshDatabase;


    public function testItHasExpectedAttributes(): void
    {
        $carer = factory(Carer::class)->make();
        $this->assertNotNull($carer->name);
    }


    public function testItCanHaveAFamily(): void
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

    // ── Partial-update encryption integrity ───────────────────────────────────
    //
    // Regression tests for the double-encrypt bug: when only one CipherSweet-
    // protected field is updated before save(), the other field's raw DB
    // ciphertext must first be decrypted to plaintext so CipherSweet encrypts
    // plaintext → fresh ciphertext, not ciphertext → corrupted double-ciphertext.

    public function testSavingWithOnlyEmailUpdatedDoesNotCorruptTelnosecret(): void
    {
        $family = factory(Family::class)->create();
        $carer = factory(Carer::class)->make([
            'emailsecret' => 'original@example.com',
            'telnosecret' => '01234567890',
        ]);
        $family->carers()->save($carer);

        // Reload from the DB to replicate the controller pattern: findOrFail()
        // returns a fresh model whose attributes are raw DB ciphertext.
        $carer = Carer::findOrFail($carer->id);

        // Update only the email field — telnosecret is intentionally left untouched.
        $carer->emailsecret = 'updated@example.com';
        $carer->save();

        // Reload again to confirm what was actually written to the database.
        $carer = Carer::findOrFail($carer->id);

        $this->assertSame(
            'updated@example.com',
            $carer->emailsecret->reveal(),
            'The updated field must decrypt to the new value.'
        );
        $this->assertSame(
            '01234567890',
            $carer->telnosecret->reveal(),
            'The untouched field must still decrypt to its original value — '
            . 'it must not have been double-encrypted during the partial update.'
        );
    }

    public function testSavingWithOnlyTelnoUpdatedDoesNotCorruptEmailsecret(): void
    {
        $family = factory(Family::class)->create();
        $carer = factory(Carer::class)->make([
            'emailsecret' => 'original@example.com',
            'telnosecret' => '01234567890',
        ]);
        $family->carers()->save($carer);

        $carer = Carer::findOrFail($carer->id);

        // Update only the telno field — emailsecret is intentionally left untouched.
        $carer->telnosecret = '09876543210';
        $carer->save();

        $carer = Carer::findOrFail($carer->id);

        $this->assertSame(
            '09876543210',
            $carer->telnosecret->reveal(),
            'The updated field must decrypt to the new value.'
        );
        $this->assertSame(
            'original@example.com',
            $carer->emailsecret->reveal(),
            'The untouched field must still decrypt to its original value — '
            . 'it must not have been double-encrypted during the partial update.'
        );
    }

    public function testSavingWithBothSecretsUpdatedPreservesBothValues(): void
    {
        // Confirms the happy path is unaffected: when both encrypted fields
        // are explicitly updated, both must reflect the new values.
        $family = factory(Family::class)->create();
        $carer = factory(Carer::class)->make([
            'emailsecret' => 'original@example.com',
            'telnosecret' => '01234567890',
        ]);
        $family->carers()->save($carer);

        $carer = Carer::findOrFail($carer->id);

        $carer->emailsecret = 'both@example.com';
        $carer->telnosecret = '00000000000';
        $carer->save();

        $carer = Carer::findOrFail($carer->id);

        $this->assertSame('both@example.com', $carer->emailsecret->reveal());
        $this->assertSame('00000000000', $carer->telnosecret->reveal());
    }

    public function testSavingWithNeitherSecretUpdatedPreservesBothValues(): void
    {
        // Confirms that saving for an unrelated reason (e.g., a plain-column
        // change) does not corrupt either encrypted field.
        $family = factory(Family::class)->create();
        $carer = factory(Carer::class)->make([
            'emailsecret' => 'safe@example.com',
            'telnosecret' => '01234567890',
        ]);
        $family->carers()->save($carer);

        $carer = Carer::findOrFail($carer->id);

        // Change only a plain field; leave both encrypted fields untouched.
        $carer->name = 'Updated Name';
        $carer->save();

        $carer = Carer::findOrFail($carer->id);

        $this->assertSame('Updated Name', $carer->name);
        $this->assertSame(
            'safe@example.com',
            $carer->emailsecret->reveal(),
            'emailsecret must survive a save that did not touch it.'
        );
        $this->assertSame(
            '01234567890',
            $carer->telnosecret->reveal(),
            'telnosecret must survive a save that did not touch it.'
        );
    }
}

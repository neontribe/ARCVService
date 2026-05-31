<?php

namespace Tests\Console\Commands;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Console\Commands\RetireCentre;
use App\Family;
use App\Note;
use App\Registration;
use App\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use RuntimeException;
use Tests\TestCase;

class RetireCentreCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the retire command with --force to skip the confirmation prompt.
     */
    private function retireCentre(int $id, array $options = []): PendingCommand
    {
        return $this->artisan('arc:retireCentre', array_merge(
            ['centre' => $id, '--force' => true],
            $options
        ));
    }

    // --- validation ---

    public function testItErrorsWhenCentreIsNotFound(): void
    {
        $this->artisan('arc:retireCentre', ['centre' => 99999, '--force' => true])
            ->assertExitCode(1);
    }

    public function testItAbortsWhenConfirmationIsDeclined(): void
    {
        $centre = factory(Centre::class)->create();

        $this->artisan('arc:retireCentre', ['centre' => $centre->id])
            ->expectsConfirmation('Proceed?', 'no')
            ->assertExitCode(0);

        // Row should still exist and not have been soft-deleted
        $this->assertDatabaseHas('centres', ['id' => $centre->id, 'deleted_at' => null]);
    }

    public function testItProceedsWhenConfirmationIsAccepted(): void
    {
        $centre = factory(Centre::class)->create();

        $this->artisan('arc:retireCentre', ['centre' => $centre->id])
            ->expectsConfirmation('Proceed?', 'yes')
            ->assertExitCode(0);

        $this->assertSoftDeleted('centres', ['id' => $centre->id]);
    }

    // --- base retirement ---

    public function testItSoftDeletesTheCentre(): void
    {
        $centre = factory(Centre::class)->create();

        $this->retireCentre($centre->id)->assertExitCode(0);

        $this->assertSoftDeleted('centres', ['id' => $centre->id]);
    }

    public function testItMarksFamiliesAsLeft(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->retireCentre($centre->id);

        $family = $registration->family->fresh();
        $this->assertNotNull($family->leaving_on);
        $this->assertEquals('centre_retired', $family->leaving_reason);
    }

    public function testItOnlyMarksFamiliesRegisteredAtTheRetiringCentre(): void
    {
        $centreA = factory(Centre::class)->create();
        $centreB = factory(Centre::class)->create();

        $registrationA = factory(Registration::class)->create(['centre_id' => $centreA->id]);
        $registrationB = factory(Registration::class)->create(['centre_id' => $centreB->id]);

        $this->retireCentre($centreA->id);

        $this->assertNotNull($registrationA->family->fresh()->leaving_on);
        $this->assertNull($registrationB->family->fresh()->leaving_on);
    }

    // --- centre user retirement ---

    public function testItSoftDeletesCentreUsersWithNoRemainingCentre(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = factory(CentreUser::class)->create(['centre_id' => $centre->id]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        $this->retireCentre($centre->id);

        $this->assertSoftDeleted('centre_users', ['id' => $centreUser->id]);
    }

    public function testItDoesNotSoftDeleteCentreUsersWithRemainingCentres(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $centreUser = factory(CentreUser::class)->create(['centre_id' => $retiringCentre->id]);
        $centreUser->centres()->attach($retiringCentre->id, ['homeCentre' => true]);
        $centreUser->centres()->attach($otherCentre->id, ['homeCentre' => false]);

        $this->retireCentre($retiringCentre->id);

        $this->assertNull(CentreUser::find($centreUser->id)->deleted_at);
    }

    public function testItDetachesTheRetiringCentreFromUsersWithRemainingCentres(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $centreUser = factory(CentreUser::class)->create(['centre_id' => $retiringCentre->id]);
        $centreUser->centres()->attach($retiringCentre->id, ['homeCentre' => true]);
        $centreUser->centres()->attach($otherCentre->id, ['homeCentre' => false]);

        $this->retireCentre($retiringCentre->id);

        $remainingCentreIds = $centreUser->fresh()->centres()->pluck('centres.id');
        $this->assertNotContains($retiringCentre->id, $remainingCentreIds);
        $this->assertContains($otherCentre->id, $remainingCentreIds);
    }

    public function testItPromotesARemainingCentreToHomeWhenHomeIsRetired(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $centreUser = factory(CentreUser::class)->create(['centre_id' => $retiringCentre->id]);
        $centreUser->centres()->attach($retiringCentre->id, ['homeCentre' => true]);
        $centreUser->centres()->attach($otherCentre->id, ['homeCentre' => false]);

        $this->retireCentre($retiringCentre->id);

        $pivot = DB::table('centre_centre_user')
            ->where('centre_user_id', $centreUser->id)
            ->where('centre_id', $otherCentre->id)
            ->first();

        $this->assertTrue((bool)$pivot->homeCentre);
    }

    public function testItDoesNotAlterHomeCentreWhenAnotherIsAlreadyHome(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $homeCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $centreUser = factory(CentreUser::class)->create(['centre_id' => $homeCentre->id]);
        $centreUser->centres()->attach($retiringCentre->id, ['homeCentre' => false]);
        $centreUser->centres()->attach($homeCentre->id, ['homeCentre' => true]);
        $centreUser->centres()->attach($otherCentre->id, ['homeCentre' => false]);

        $this->retireCentre($retiringCentre->id);

        $homePivot = DB::table('centre_centre_user')
            ->where('centre_user_id', $centreUser->id)
            ->where('centre_id', $homeCentre->id)
            ->first();
        $this->assertTrue((bool)$homePivot->homeCentre);

        $otherPivot = DB::table('centre_centre_user')
            ->where('centre_user_id', $centreUser->id)
            ->where('centre_id', $otherCentre->id)
            ->first();
        $this->assertFalse((bool)$otherPivot->homeCentre);
    }

    // --- voucher freeing ---

    public function testItFreesVouchersFromUndisbursedBundles(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $bundle = factory(Bundle::class)->create([
            'registration_id' => $registration->id,
            'disbursed_at' => null,
        ]);
        $voucher = factory(Voucher::class)->create(['bundle_id' => $bundle->id]);

        $this->retireCentre($centre->id);

        $this->assertNull($voucher->fresh()->bundle_id);
    }

    public function testItDoesNotFreeVouchersFromDisbursedBundles(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $bundle = factory(Bundle::class)->create([
            'registration_id' => $registration->id,
            'disbursed_at' => now(),
        ]);
        $voucher = factory(Voucher::class)->create(['bundle_id' => $bundle->id]);

        $this->retireCentre($centre->id);

        $this->assertEquals($bundle->id, $voucher->fresh()->bundle_id);
    }

    public function testItDoesNotFreeVouchersBelongingToOtherCentres(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $bundle = factory(Bundle::class)->create([
            'registration_id' => factory(Registration::class)->create(['centre_id' => $otherCentre->id])->id,
            'disbursed_at' => null,
        ]);
        $voucher = factory(Voucher::class)->create(['bundle_id' => $bundle->id]);

        $this->retireCentre($retiringCentre->id);

        $this->assertEquals($bundle->id, $voucher->fresh()->bundle_id);
    }

    public function testRegistrationsArePreservedByDefault(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->retireCentre($centre->id);

        $this->assertNotNull(Registration::find($registration->id));
    }

    public function testBundlesArePreservedByDefault(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $bundle = factory(Bundle::class)->create(['registration_id' => $registration->id]);

        $this->retireCentre($centre->id);

        $this->assertNotNull(Bundle::find($bundle->id));
    }

    // --- --remove-registrations ---

    public function testRemoveRegistrationsDeletesRegistrations(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->retireCentre($centre->id, ['--remove-registrations' => true]);

        $this->assertNull(Registration::find($registration->id));
    }

    public function testRemoveRegistrationsDeletesBundles(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $bundle = factory(Bundle::class)->create(['registration_id' => $registration->id]);

        $this->retireCentre($centre->id, ['--remove-registrations' => true]);

        $this->assertNull(Bundle::find($bundle->id));
    }

    public function testRemoveRegistrationsNullifiesDisbursedBundleVouchersBeforeDeletion(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $bundle = factory(Bundle::class)->create([
            'registration_id' => $registration->id,
            'disbursed_at' => now(),
        ]);
        $voucher = factory(Voucher::class)->create(['bundle_id' => $bundle->id]);

        $this->retireCentre($centre->id, ['--remove-registrations' => true]);

        $this->assertNull($voucher->fresh()->bundle_id);
    }

    public function testRemoveRegistrationsDoesNotAffectOtherCentres(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $otherRegistration = factory(Registration::class)->create(['centre_id' => $otherCentre->id]);
        factory(Registration::class)->create(['centre_id' => $retiringCentre->id]);

        $this->retireCentre($retiringCentre->id, ['--remove-registrations' => true]);

        $this->assertNotNull(Registration::find($otherRegistration->id));
    }

    // --- --remove-families ---

    public function testRemoveFamiliesImpliesRemoveRegistrations(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertNull(Registration::find($registration->id));
    }

    public function testRemoveFamiliesDeletesFamilies(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $familyId = $registration->family->id;

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertNull(Family::find($familyId));
    }

    public function testRemoveFamiliesDeletesCarers(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $family = $registration->family;
        $carer = factory(Carer::class)->create(['family_id' => $family->id]);

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertNull(Carer::find($carer->id));
    }

    public function testRemoveFamiliesDeletesChildren(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $family = $registration->family;
        $child = factory(Child::class)->create(['family_id' => $family->id]);

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertNull(Child::find($child->id));
    }

    public function testRemoveFamiliesDeletesNotes(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = factory(CentreUser::class)->create(['centre_id' => $centre->id]);
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $note = factory(Note::class)->create([
            'family_id' => $registration->family->id,
            'user_id' => $centreUser->id,
        ]);

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertNull(Note::find($note->id));
    }

    public function testRemoveFamiliesDeletesBlindIndexesForCarers(): void
    {
        $centre = factory(Centre::class)->create();
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $family = $registration->family;
        $carer = factory(Carer::class)->create(['family_id' => $family->id]);

        // Seed a blind index row for the carer as the encryption layer would
        DB::table('blind_indexes')->insert([
            'indexable_type' => (new Carer())->getMorphClass(),
            'indexable_id' => $carer->id,
            'name' => 'email',
            'value' => hash('sha256', 'test@example.com'),
        ]);

        $this->retireCentre($centre->id, ['--remove-families' => true]);

        $this->assertDatabaseMissing('blind_indexes', [
            'indexable_type' => (new Carer())->getMorphClass(),
            'indexable_id' => $carer->id,
        ]);
    }

    public function testRemoveFamiliesDoesNotAffectFamiliesAtOtherCentres(): void
    {
        $retiringCentre = factory(Centre::class)->create();
        $otherCentre = factory(Centre::class)->create();

        $otherFamily = factory(Registration::class)
            ->create(['centre_id' => $otherCentre->id])
            ->family;

        factory(Registration::class)->create(['centre_id' => $retiringCentre->id]);

        $this->retireCentre($retiringCentre->id, ['--remove-families' => true]);

        $this->assertNotNull(Family::find($otherFamily->id));
    }

    // --- rollback on failure ---

    public function testNothingIsChangedIfTheCommandFails(): void
    {
        $centre = factory(Centre::class)->create();
        factory(Registration::class)->create(['centre_id' => $centre->id]);

        // Partial-mock the command to throw during the transaction, after some
        // work has started. SET FOREIGN_KEY_CHECKS is MySQL-specific and not
        // available in the SQLite test environment.
        $this->partialMock(RetireCentre::class, function ($mock) {
            $mock->shouldReceive('markFamiliesAsLeft')
                ->andThrow(new RuntimeException('Forced failure'));
        });

        $this->retireCentre($centre->id)->assertExitCode(1);

        // Centre should not have been soft-deleted
        $this->assertDatabaseHas('centres', ['id' => $centre->id, 'deleted_at' => null]);
    }
}

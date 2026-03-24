<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\Family;
use App\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MoveFamilyRegistrationCentreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
    }

    protected function tearDown(): void
    {
        // tidy up last test
        Storage::disk()->deleteDirectory('/');
        parent::tearDown();
    }

    public function testItRequiresEitherPairOfArgumentsOrCsv(): void
    {
        $this->artisan('arc:move-family-reg')
            ->expectsOutput('Provide either a family_id and centre_id OR --csv=path')
            ->assertExitCode(1);
    }

    public function testItRejectsMixingDirectArgumentsWithCsv(): void
    {
        $csv = $this->makeCsv([
            ['RVID', 'Centre'],
            ['ABC123', 'Target Centre'],
        ], 'move-family-');

        $this->artisan('arc:move-family-reg', [
            'family_id' => 1,
            'centre_id' => 2,
            '--csv' => $csv,
        ])
            ->expectsOutput('Provide either a family_id and centre_id OR --csv=path')
            ->assertExitCode(1);
    }

    public function testItFailsWhenCsvFileIsMissing(): void
    {
        $missing = Storage::path('missing-move.csv');

        $this->artisan('arc:move-family-reg', [
            '--csv' => $missing,
        ])
            ->expectsOutput("CSV file not found: $missing")
            ->assertExitCode(1);
    }

    public function testItFailsWhenTargetCentreDoesNotExist(): void
    {
        $family = factory(Family::class)->create();

        $this->artisan('arc:move-family-reg', [
            'family_id' => $family->id,
            'centre_id' => 999999,
            '--force' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY $family->id ====")
            ->expectsOutput('Centre 999999 not found.')
            ->expectsOutput('Batch complete.')
            ->expectsOutput('Failed family IDs:')
            ->expectsOutput((string) $family->id)
            ->assertExitCode(1);
    }

    public function testItFailsWhenFamilyDoesNotExist(): void
    {
        $centre = factory(Centre::class)->create();

        $this->artisan('arc:move-family-reg', [
            'family_id' => 999999,
            'centre_id' => $centre->id,
            '--force' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput('==== FAMILY 999999 ====')
            ->expectsOutput('Family 999999 not found.')
            ->expectsOutput('Batch complete.')
            ->expectsOutput('Failed family IDs:')
            ->expectsOutput('999999')
            ->assertExitCode(1);
    }

    public function testDryRunShowsSummaryAndDoesNotMutateRegistrations(): void
    {
        $from = factory(Centre::class)->create(['name' => 'From Centre']);
        $to = factory(Centre::class)->create(['name' => 'To Centre']);

        $family = factory(Family::class)->create([
            'initial_centre_id' => $from->id,
        ]);

        $registrations = factory(Registration::class, 2)->create([
            'family_id' => $family->id,
            'centre_id' => $from->id,
        ]);

        $this->artisan('arc:move-family-reg', [
            'family_id' => $family->id,
            'centre_id' => $to->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Dry run complete — nothing deleted.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0);

        foreach ($registrations as $registration) {
            $this->assertDatabaseHas('registrations', [
                'id' => $registration->id,
                'centre_id' => $from->id,
            ]);
        }
    }

    public function testItSkipsWhenConfirmationIsDeclined(): void
    {
        $from = factory(Centre::class)->create(['name' => 'From Centre']);
        $to = factory(Centre::class)->create(['name' => 'To Centre']);

        $family = factory(Family::class)->create([
            'initial_centre_id' => $from->id,
        ]);

        $registration = factory(Registration::class)->create([
            'family_id' => $family->id,
            'centre_id' => $from->id,
        ]);

        $this->artisan('arc:move-family-reg', [
            'family_id' => $family->id,
            'centre_id' => $to->id,
        ])
            ->expectsConfirmation(
                "This will permanently move family {$family->id} and related data to {$to->name}. Continue?"
            )
            ->expectsOutput('Skipped')
            ->expectsOutput('Batch complete.')
            ->expectsOutput('Failed family IDs:')
            ->expectsOutput((string) $family->id)
            ->assertExitCode(1);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'centre_id' => $from->id,
        ]);
    }

    public function testItMovesRegistrationsWhenForced(): void
    {
        $from = factory(Centre::class)->create(['name' => 'From Centre']);
        $to = factory(Centre::class)->create(['name' => 'To Centre']);

        $family = factory(Family::class)->create([
            'initial_centre_id' => $from->id,
        ]);

        $registrations = factory(Registration::class, 3)->create([
            'family_id' => $family->id,
            'centre_id' => $from->id,
        ]);

        $this->artisan('arc:move-family-reg', [
            'family_id' => $family->id,
            'centre_id' => $to->id,
            '--force' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Family Registration permanently moved.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0);

        foreach ($registrations as $registration) {
            $this->assertDatabaseHas('registrations', [
                'id' => $registration->id,
                'centre_id' => $to->id,
            ]);
        }
    }

    public function testItProcessesCsvDedupesPairsAndIgnoresInvalidRows(): void
    {
        $oldCentre = factory(Centre::class)->create([
            'name' => 'Old Centre',
            'prefix' => 'AB',
        ]);

        $newCentre = factory(Centre::class)->create([
            'name' => 'New Centre',
            'prefix' => 'XY',
        ]);

        $family = factory(Family::class)->create([
            'initial_centre_id' => $oldCentre->id,
            'centre_sequence' => 123,
        ]);

        factory(Registration::class, 2)->create([
            'family_id' => $family->id,
            'centre_id' => $oldCentre->id,
        ]);

        $csvPath = $this->makeCsv([
            ['RVID', 'Centre'],
            [$family->rvid, $newCentre->name],
            [$family->rvid, $newCentre->name],
            ['NOTREAL999', $newCentre->name],
            [$family->rvid, 'Missing Centre'],
        ], 'move-family-');

        $this->artisan('arc:move-family-reg', [
            '--csv' => $csvPath,
            '--force' => true,
        ])
            ->expectsOutput('Invalid RVID: NOTREAL999')
            ->expectsOutput('Invalid Centre: Missing Centre')
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Family Registration permanently moved.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0)
        ;
        $this->assertDatabaseCount('registrations', 2);

        $regsCount = Registration::where('family_id', $family->id)
            ->where('centre_id', $newCentre->id)
            ->count();

        $this->assertSame(2, $regsCount);
    }

    private function makeCsv(array $rows, string $name): string
    {
        $path = uniqid($name, true) . '.csv';
        $stream = fopen('php://temp', 'wb+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        Storage::put($path, stream_get_contents($stream));
        fclose($stream);

        return $path;
    }
}

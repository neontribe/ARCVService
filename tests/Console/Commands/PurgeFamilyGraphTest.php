<?php

namespace Tests\Console\Commands;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Child;
use App\Family;
use App\Registration;
use App\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeFamilyGraphTest extends TestCase
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

    public function testItRequiresFamilyIdOrCsv(): void
    {
        $this->artisan('arc:purge-family')
            ->expectsOutput('Provide either a family_id OR --csv=path')
            ->assertExitCode(1);
    }

    public function testItFailsWhenCsvFileIsMissing(): void
    {
        $missing = Storage::path('missing-purge.csv');

        $this->artisan('arc:purge-family', [
            '--csv' => $missing,
        ])
            ->expectsOutput("CSV file not found: {$missing}")
            ->assertExitCode(1);
    }

    public function testItFailsWhenFamilyIsMissing(): void
    {
        $this->artisan('arc:purge-family', [
            'family_id' => 999999,
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

    public function testDryRunReportsButDoesNotDeleteAnything(): void
    {
        $family = factory(Family::class)->create();

        $registration = factory(Registration::class)->create([
            'family_id' => $family->id,
        ]);

        $bundle = factory(Bundle::class)->create([
            'registration_id' => $registration->id,
        ]);

        $voucher = factory(Voucher::class)->create([
            'bundle_id' => $bundle->id,
        ]);

        $child = factory(Child::class)->create([
            'family_id' => $family->id,
        ]);

        $carer = factory(Carer::class)->create([
            'family_id' => $family->id,
        ]);

        $this->artisan('arc:purge-family', [
            'family_id' => $family->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Dry run complete — nothing deleted.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('families', ['id' => $family->id]);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id]);
        $this->assertDatabaseHas('bundles', ['id' => $bundle->id]);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'bundle_id' => $bundle->id]);
        $this->assertDatabaseHas('children', ['id' => $child->id]);
        $this->assertDatabaseHas('carers', ['id' => $carer->id]);
    }

    public function testItSkipsWhenConfirmationIsDeclined(): void
    {
        $family = factory(Family::class)->create();

        $this->artisan('arc:purge-family', [
            'family_id' => $family->id,
        ])
            ->expectsConfirmation(
                "This will permanently purge family {$family->id} and related data. Continue?"
            )
            ->expectsOutput('Skipped')
            ->expectsOutput('Batch complete.')
            ->expectsOutput('Failed family IDs:')
            ->expectsOutput((string) $family->id)
            ->assertExitCode(1);

        $this->assertDatabaseHas('families', ['id' => $family->id]);
    }

    public function testItPurgesTheEntireFamilyGraphWhenForced(): void
    {
        $family = factory(Family::class)->create();

        $registrationA = factory(Registration::class)->create([
            'family_id' => $family->id,
        ]);

        $registrationB = factory(Registration::class)->create([
            'family_id' => $family->id,
        ]);

        $bundleA = factory(Bundle::class)->create([
            'registration_id' => $registrationA->id,
        ]);

        $bundleB = factory(Bundle::class)->create([
            'registration_id' => $registrationB->id,
        ]);

        $voucherA = factory(Voucher::class)->create([
            'bundle_id' => $bundleA->id,
        ]);

        $voucherB = factory(Voucher::class)->create([
            'bundle_id' => $bundleB->id,
        ]);

        $children = factory(Child::class, 2)->create([
            'family_id' => $family->id,
        ]);

        $activeCarer = factory(Carer::class)->create([
            'family_id' => $family->id,
        ]);

        $trashedCarer = factory(Carer::class)->create([
            'family_id' => $family->id,
        ]);

        $trashedCarer->delete();

        $this->artisan('arc:purge-family', [
            'family_id' => $family->id,
            '--force' => true,
        ])
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Family graph permanently deleted.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('families', ['id' => $family->id]);
        $this->assertDatabaseMissing('registrations', ['id' => $registrationA->id]);
        $this->assertDatabaseMissing('registrations', ['id' => $registrationB->id]);
        $this->assertDatabaseMissing('bundles', ['id' => $bundleA->id]);
        $this->assertDatabaseMissing('bundles', ['id' => $bundleB->id]);

        foreach ($children as $child) {
            $this->assertDatabaseMissing('children', ['id' => $child->id]);
        }

        $this->assertDatabaseMissing('carers', ['id' => $activeCarer->id]);
        $this->assertDatabaseMissing('carers', ['id' => $trashedCarer->id]);

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucherA->id,
            'bundle_id' => null,
        ]);

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucherB->id,
            'bundle_id' => null,
        ]);
    }

    public function testItProcessesCsvDedupesIdsAndIgnoresInvalidRvids(): void
    {
        $centre = factory(Centre::class)->create([
            'prefix' => 'RV',
        ]);

        $family = factory(Family::class)->create([
            'initial_centre_id' => $centre->id,
            'centre_sequence' => 42,
        ]);

        $csv = $this->makeCsv([
            ['RVID'],
            [$family->rvid],
            [$family->rvid],
            ['BAD999'],
        ], 'purge-family-');

        $this->artisan('arc:purge-family', [
            '--csv' => $csv,
            '--force' => true,
        ])
            ->expectsOutput('Invalid rvid: BAD999')
            ->expectsOutput('Processing 1 families')
            ->expectsOutput("==== FAMILY {$family->id} ====")
            ->expectsOutput('Family graph permanently deleted.')
            ->expectsOutput('Batch complete.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('families', ['id' => $family->id]);
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

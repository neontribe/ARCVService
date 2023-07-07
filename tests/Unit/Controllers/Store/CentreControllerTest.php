<?php


namespace Tests\Unit\Controllers\Store;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CentreControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Centre $centre */
    protected $centre;

    /** @var CentreUser $centreUser */
    protected $centreUser;

    /** @var Collection */
    protected $registrations;

    protected $dashboard_route;

    public function setUp(): void
    {
        parent::setUp();

        // Set up a Centre
        $this->centre = factory(Centre::class)->create();

        // Create an FM User
        $this->centreUser = factory(CentreUser::class)->state('FMUser')->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Set up a new SP sponsor and centre
        $this->spSponsor = factory(Sponsor::class)->create([
            'programme' => 1
        ]);
        $this->spCentre = factory(Centre::class)->create(['sponsor_id' => $this->spSponsor->id]);
        $this->centreUser->centres()->attach($this->spCentre->id, ['homeCentre' => false]);

        // Create a bunch of registrations
        $this->registrations = factory(Registration::class, 15)->create([
            'centre_id' => $this->centre->id
        ]);

        $this->spRegistrations = factory(Registration::class, 12)->create([
            'centre_id' => $this->spCentre->id
        ]);

        foreach ($this->spRegistrations as $key => $reg) {
            foreach ($reg->family->children as $key => $child) {
                if ($key === 0) {
                    $child->is_pri_carer = 1;
                    $child->save();
                }
            }
        }

        // Add some bundles
        $this->registrations->each(function ($registration, $key) {
            $bundle = $registration->currentBundle();
            // Add some random vouchers
            $vouchers = factory(Voucher::class, rand(1, 3) * 3)->state('dispatched')->create();
            $bundle->alterVouchers($vouchers, [], $bundle);
            // set it to be disbursed some time in the past, with some variation.
            $bundle->disbursed_at = Carbon::today()->startOfDay()->addHour($key);
            $bundle->save();
        });

        $this->spRegistrations->each(function ($registration, $key) {
            $bundle = $registration->currentBundle();
            // Add some random vouchers
            $vouchers = factory(Voucher::class, rand(1, 3) * 3)->state('dispatched')->create();
            $bundle->alterVouchers($vouchers, [], $bundle);
            // set it to be disbursed some time in the past, with some variation.
            $bundle->disbursed_at = Carbon::today()->startOfDay()->addHour($key);
            $bundle->save();
        });

        $this->dashboard_route = route('store.dashboard');
    }

    /** @test */
    public function testItCanDownloadAStandardRegistrationsSpreadsheet()
    {
        $sheet_route = route('store.centres.registrations.summary');

        $content = $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($sheet_route)
            ->response
            ->getContent()
        ;

        // Create an array of lines and filter off blank ones (default array_filter behaviour)
        $data = array_filter(explode(PHP_EOL, $content));
        // Shift the headers off.
        $headers = str_getcsv(array_shift($data));

        // The expected headers
        $expected_headers = [
            "RVID",
            "Area",
            "Distribution Centre",
            "Primary Carer",
            "Entitlement",
            "Last Collection",
            "Total Children",
            "Eligible Children",
            "Due Date",
            "Join Date",
            "Leaving Date",
            "Leaving Reason",
            "Rejoin Date",
            "Leave Count",
            "Days on programme"
        ];

        // Check the expected headers are present.
        foreach ($expected_headers as $expected) {
            $this->assertContains($expected, $headers);
        }

        // Remap the headers onto each line as keys
        $lines = array_map(
            function ($line) use ($headers) {
                return array_combine($headers, str_getcsv($line));
            },
            $data
        );

        // There are the right amount of lines.
        $this->assertEquals($this->registrations->count(), count($lines));

        // Prep for testing hashes
        $hashes = [];

        // Test each output line
        foreach ($lines as $line) {
            // Make a hash, test it and add it to the end
            $hash =
                $line["Area"] . '#' .
                $line["Distribution Centre"] . '#' .
                $line["Primary Carer"];

            if (!empty($hashes)) {
                // Check that we're greater than or equal to the last hash
                $this->assertGreaterThanOrEqual(0, strcmp($hash, last($hashes)));
            }

            // Add it on the end for next round
            $hashes[] = $hash;

            $reg = $this->registrations->first(function ($model) use ($line) {
                return $model->family->rvid = $line["RVID"];
            });
            // The database has an record from the output.
            $this->assertNotFalse($reg);

            // It has the correct centre name
            $this->assertEquals($reg->centre->name, $line["Distribution Centre"]);

            // It has the correct area/sponsor name
            $this->assertEquals($reg->centre->sponsor->name, $line["Area"]);

            // That thing has returned a top disbursed bundle
            $bundle = $reg->bundles()->whereNotNull('disbursed_at')->orderBy('disbursed_at')->first();
            $this->assertNotFalse($bundle);
            // That is the same date as this line.
            $this->assertEquals($bundle->disbursed_at->format('d/m/Y'), $line["Last Collection"]);
        }
    }

    /** @test */
    public function testItCanDownloadASocialPrescriptionsRegistrationsSpreadsheet()
    {
        $sheet_route = route('store.centres.registrations.summary', ['programme' => 1]);

        $content = $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($sheet_route)
            ->response
            ->getContent()
        ;

        // Create an array of lines and filter off blank ones (default array_filter behaviour)
        $data = array_filter(explode(PHP_EOL, $content));
        // Shift the headers off.
        $headers = str_getcsv(array_shift($data));

        // The expected headers
        $expected_headers = [
            "RVID",
            "Area",
            "Distribution Centre",
            "Main Participant",
            "Entitlement",
            "Last Collection",
            "Eligible Household Members",
            "Main Participant DoB",
            "Join Date",
            "Leaving Date",
            "Leaving Reason",
            "Rejoin Date",
            "Leave Count",
            "Days on programme"
        ];
        // The unexpected headers
        $unexpected_headers = [
            "Total Children",
            "Eligible Children",
            "Family Eligibility (HSBS)",
            "Family Eligibility (NRPF)",
            "Eligible From",
            "Due Date",
        ];

        // Check the expected headers are present.
        foreach ($expected_headers as $expected) {
            $this->assertContains($expected, $headers);
        }
        // Check the unexpected headers are not present.
        foreach ($unexpected_headers as $unexpected) {
            $this->assertNotContains($unexpected, $headers);
        }

        // Remap the headers onto each line as keys
        $lines = array_map(
            function ($line) use ($headers) {
                return array_combine($headers, str_getcsv($line));
            },
            $data
        );

        // There are the right amount of lines.
        $this->assertEquals($this->spRegistrations->count(), count($lines));

        // Prep for testing hashes
        $hashes = [];

        // Test each output line
        foreach ($lines as $line) {
            // Make a hash, test it and add it to the end
            $hash =
                $line["Area"] . '#' .
                $line["Distribution Centre"] . '#' .
                $line["Main Participant"];

            if (!empty($hashes)) {
                // Check that we're greater than or equal to the last hash
                $this->assertGreaterThanOrEqual(0, strcmp($hash, last($hashes)));
            }

            // Add it on the end for next round
            $hashes[] = $hash;

            $reg = $this->spRegistrations->first(function ($model) use ($line) {
                return $model->family->rvid = $line["RVID"];
            });
            // The database has an record from the output.
            $this->assertNotFalse($reg);

            // It has the correct centre name
            $this->assertEquals($reg->centre->name, $line["Distribution Centre"]);

            // It has the correct area/sponsor name
            $this->assertEquals($reg->centre->sponsor->name, $line["Area"]);

            // That thing has returned a top disbursed bundle
            $bundle = $reg->bundles()->whereNotNull('disbursed_at')->orderBy('disbursed_at')->first();
            $this->assertNotFalse($bundle);
            // That is the same date as this line.
            $this->assertEquals($bundle->disbursed_at->format('d/m/Y'), $line["Last Collection"]);
        }
    }

    public function testACentreWithNoRegistrationsCanDownloadAnEmptyRecord()
    {
        $sponsor = factory(Sponsor::class)->create();
        $centre = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $this->assertEquals(1, $sponsor->centres->count());

        $centreUser = factory(CentreUser::class)->state('withDownloader')->create([
            "name"  => "test downloader",
            "email" => "testdl@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);

        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
        $centreForUser = $centre->centreUsers;
        $this->assertEquals(1, $centreForUser->count());

        $sheet_route = route('store.centre.registrations.summary', ['centre' => $centre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($sheet_route)
            ->response
            ->getContent()
        ;

        $this->actingAs($centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($sheet_route)
            ->followRedirects()
            ->assertResponseOK()
        ;
    }
}

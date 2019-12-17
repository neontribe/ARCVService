<?php


namespace Tests\Unit\Controllers\Store;

use App\Registration;
use App\Centre;
use App\CentreUser;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class CentreControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $centre;
    protected $centreUser;
    /** @var Collection */
    protected $registrations;

    public function setUp()
    {
        parent::setUp();

        // Set up a Centre
        $this->centre = factory(Centre::class)->create();

        // Create an FM User
        $this->centreUser = factory(CentreUser::class, 'FMUser')->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Create a bunch of registrations
        $this->registrations = factory(Registration::class, 15)->create([
            'centre_id' => $this->centre->id
        ]);

        // Add some bundles
        $this->registrations->each(function ($registration, $key) {
            $bundle = $registration->currentBundle();
            // Add some random vouchers
            $vouchers = factory(Voucher::class, 'dispatched', rand(1, 3) * 3)->create();
            $bundle->alterVouchers($vouchers, [], $bundle);
            // set it to be disbursed some time in the past, with some variation.
            $bundle->disbursed_at = Carbon::today()->startOfDay()->addHour($key);
            $bundle->save();
        });
    }

    /** @test */
    public function testItCanDownloadARegistrationsSpreadsheet()
    {
        $dashboard_route = route('store.dashboard');
        $sheet_route = route('store.centres.registrations.summary');

        $content = $this->actingAs($this->centreUser, 'store')
            ->visit($dashboard_route)
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
            "Centre",
            "Primary Carer",
            "Entitlement",
            "Last Collection",
            "Eligible Children",
            "Due Date",
            "Join Date",
            "Leaving Date",
            "Leaving Reason"
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
                $line["Centre"] . '#' .
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
            $this->assertEquals($reg->centre->name, $line["Centre"]);

            // It has the correct area/sponsor name
            $this->assertEquals($reg->centre->sponsor->name, $line["Area"]);

            // That thing has returned a top disbursed bundle
            $bundle = $reg->bundles()->whereNotNull('disbursed_at')->orderBy('disbursed_at')->first();
            $this->assertNotFalse($bundle);
            // That is the same date as this line.
            $this->assertEquals($bundle->disbursed_at->format('d/m/Y'), $line["Last Collection"]);
        }
    }
}

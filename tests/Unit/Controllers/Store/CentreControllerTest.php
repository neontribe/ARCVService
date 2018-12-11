<?php


namespace Tests\Unit\Controllers\Store;

use App\Registration;
use App\Centre;
use App\CentreUser;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Maatwebsite\Excel;
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
        $this->centreUser = factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "role" => "foodmatters_user"
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
        // remap the headers onto each line as keys
        $lines = array_map(
            function ($line) use ($headers) {
                return array_combine($headers, str_getcsv($line));
            },
            $data
        );

        // There are the right amount of lines.
        $this->assertEquals($this->registrations->count(), count($lines));

        // Test each output line
        foreach ($lines as $line) {
            $reg = $this->registrations->first(function ($model) use ($line) {
                return $model->family->rvid = $line["RVID"];
            });
            // It returned a not-false thing.
            $this->assertNotFalse($reg);
            // That thing has returns a top disbursed bundle
            $bundle = $reg->bundles()->whereNotNull('disbursed_at')->orderBy('disbursed_at')->first();
            $this->assertNotFalse($bundle);
            // That is the same date as this line.
            $this->assertEquals($bundle->disbursed_at->format('d/m/Y'), $line["Last Collection"]);
        }
    }
}

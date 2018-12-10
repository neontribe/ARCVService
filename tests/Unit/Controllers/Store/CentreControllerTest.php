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
        $this->centreUser =  factory(CentreUser::class)->create([
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
            $vouchers = factory(Voucher::class, 'dispatched', rand(1, 3) * 3)->create();
            $bundle->alterVouchers($vouchers, [], $bundle);
            // set it to be disbursed some time in the past.
            $bundle->disbursed_at = Carbon::today()->startOfDay()->addHour($key);
            $bundle->save();
        });
    }

    /** @test */
    public function testItCanDownloadARegistrationsSpreadsheet()
    {
        $route = route('store.centres.registrations.summary');
        $response = $this->actingAs($this->centreUser)
            ->visit($route);
        // See that the number of registrations is correct
        // See that the carer name is correct
        // See that the column headers are present
        // See that the Last collection date is present
    }
}

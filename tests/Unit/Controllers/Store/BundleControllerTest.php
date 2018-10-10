<?php


namespace Tests\Unit\Controllers\Store;

use Log;
use Auth;
use App\Registration;
use App\Bundle;
use App\Centre;
use App\CentreUser;
use App\Voucher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BundleControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $centre;
    protected $centreUser;

    /** @var Registration $registration */
    protected $registration;
    protected $bundle;

    protected function setUp()
    {
        parent::setUp();
        $this->centre = factory(Centre::class)->create();

        // Create a User
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
        ]);

        //  A Registration on that centre
        $this->registration = factory(Registration::class)->create([
                'centre_id' => $this->centre->id
            ]);
    }

    /** @test */
    public function testICanDeleteANamedVoucher()
    {
        /** @var Bundle $currentBundle */
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        // Make some vouchers to bundle.
        $testCodes = [
            'tst0123455',
            'tst0123456',
            'tst0123457'
        ];
        foreach ($testCodes as $testCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
            $voucher->setBundle($currentBundle);
        }

        // there should be 3 vouchers!
        $this->assertEquals(count($testCodes), $currentBundle->vouchers()->count());

        // find the first voucher
        $voucher = $this->registration->currentBundle()->first();

        $delete_route = route(
            'store.registration.voucher.delete',
            [
                'registration' => $this->registration->id,
                'voucher' => $voucher->id
            ]
        );

        // hit the route with a delete request;
        $this->actingAs($this->centreUser, 'store')
            ->delete($delete_route)
        ;

        // Reload the registration
        $this->registration->fresh();

        Log::info($currentBundle->vouchers()->get()->toArray());

        // See less vouchers
        $this->assertEquals(count($testCodes) -1, $currentBundle->vouchers()->count());
        $this->assertNull($currentBundle->vouchers()->first()->bundle_id);
    }
}
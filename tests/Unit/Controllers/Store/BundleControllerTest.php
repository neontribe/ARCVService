<?php


namespace Tests\Unit\Controllers\Store;

use Auth;
use App\Registration;
use App\Bundle;
use App\Centre;
use App\CentreUser;
use App\Voucher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Session;
use Tests\StoreTestCase;

class BundleControllerTest extends StoreTestCase
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

        // Make some vouchers
        // Make some vouchers to bundle.
        $testCodes = [
            'tst0123455',
            'tst0123456',
            'tst0123457'
        ];

        Auth::login($this->centreUser);

        foreach ($testCodes as $testCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
        }

    }

    /** @test */
    public function testICannotSubmitInvalidValuesToAppendVouchers()
    {
        $dataSets = [
            // no data
            [
                "data" => [],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is not present
            [
                "data" => ['end' => 'tst0123457'],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is present but null
            [
                "data" => ["start" => '', 'end' => 'tst0123457'],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is not a valid voucher code
            [
                "data" => ["start" => 'invalidVoucher', 'end' => 'tst0123457' ],
                "outcome" => ["start" => "The selected start is invalid."]
            ],
            // end is not a valid voucher code
            [
                "data" => ["start" => 'tst0123455', 'end' => 'invalidCode' ],
                "outcome" => ["end" => "The selected end is invalid."]
            ]
        ];

        $route = route('store.registration.voucher-manager',  [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        foreach ($dataSets as $set) {
            $response = $this->actingAs($this->centreUser, 'store')
                ->visit($route)
                ->post(
                  $post_route,
                  $set["data"]
                )
            ;
            // owrk out which field we're testing.
            $field = array_keys($set['outcome'])[0];

            // Dig out errors from Session
            $response->seeInSession('errors');
            $errors = Session::get("errors")->get($field);

            // Check our specific message is present
            $this->assertContains($set['outcome'][$field],$errors);

            // we follow that to the correct page;
            $this->followRedirects($response)
                ->seePageIs($route)
                ->assertResponseStatus(200)
            ;
        }
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
        $voucher = $currentBundle->vouchers()->first();

        $delete_route = route(
            'store.registration.voucher.delete',
            [
                'registration' => $this->registration->id,
                'voucher' => $voucher->id
            ]
        );

        // Hit the route with a delete request;
        $this->actingAs($this->centreUser, 'store')
            ->delete($delete_route)
        ;

        // refresh bundle
        $currentBundle->refresh();
        // See less vouchers
        $this->assertEquals(count($testCodes) -1, $currentBundle->vouchers()->count());

        // Refresh the detached voucher
        $voucher->refresh();
        // Assert voucher is unbundled
        $this->assertNull($voucher->bundle_id);

        // Assert voucher is back to dispatched
        $this->assertEquals('dispatched', $voucher->currentstate);
    }
}
<?php

namespace Tests\Unit\Controllers\Store;

use App\Registration;
use App\Bundle;
use App\Centre;
use App\CentreUser;
use App\Family;
use App\Sponsor;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Session;
use Tests\StoreTestCase;

class BundleControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $centre;
    protected $centreUser;
    protected $testCodes;
    /** @var Registration $registration */
    protected $registration;
    protected $bundle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->centre = factory(Centre::class)->create();

        // Create a User
        $this->centreUser = factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        //  A Registration on that centre
        $this->registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id
        ]);

        // Make some vouchers
        $this->testCodes = [
            'TST09999',
            'TST10000',
            'TST10001'
        ];

        Auth::login($this->centreUser);

        foreach ($this->testCodes as $testCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('dispatch');
        }

        $this->programme = Auth::user()->centre->sponsor->programme;

        Auth::logout();
    }

    /** @test */
    public function testICannotSubmitInvalidValuesToAppendVouchers()
    {
        $this->markTestSkipped('Waiting for fix');
        $dataSets = [
            // no data
            [
                "data" => [],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is not present
            [
                "data" => ['end' => 'tst10001'],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is present but null
            [
                "data" => ["start" => '', 'end' => 'tst10001'],
                "outcome" => ["start" => "The start field is required."]
            ],
            // start is not a valid voucher code
            [
                "data" => ["start" => 'invalidVoucher', 'end' => 'tst10001' ],
                "outcome" => ["start" => "The selected start is invalid."]
            ],
            // end is not a valid voucher code
            [
                "data" => ["start" => 'tst09999', 'end' => 'invalidCode' ],
                "outcome" => ["end" => "The selected end is invalid."]
            ],
            // end is not the same shortcode as start
            [
                "data" => ["start" => 'tst09999', 'end' => 'txt10000' ],
                "outcome" => ["end" => "The end field must be the same sponsor as the start field."]
            ],
            // end is not higher than start
            [
                "data" => ["start" => 'tst10001', 'end' => 'tst09999' ],
                "outcome" => ["end" => "The end field must be greater than the start field."]
            ],
        ];

        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        foreach ($dataSets as $set) {
            $response = $this->actingAs($this->centreUser, 'store')
                ->visit($route)
                ->post(
                    $post_route,
                    $set["data"]
                )
            ;
            // work out which field we're testing.
            $field = array_keys($set['outcome'])[0];

            // Dig out errors from Session
            $all = session()->all();
            self::assertArrayHasKey("errors", $all);
            $errors = session("errors")->get($field);

            // Check our specific message is present
            $this->assertContains($set['outcome'][$field], $errors);

            // we follow that to the correct page;
            $this->followRedirects()
                ->seePageIs($route)
                ->assertResponseStatus(200)
            ;
        }
    }

    /** @test */
    public function testIMustDisburseWithAllRelevantFields()
    {
        $dataSets = [
            [
                "data" => [
                    "collected_at" => "1", "collected_on" => "2018-07-21"
                ],
                "outcome" => [ "collected_by" => "The collected by field is required when collected at / collected on is present."],
            ],
            [
                "data" => [
                    "collected_by" => "1", "collected_on" => "2018-07-21"
                ],
                "outcome" => [ "collected_at" => "The collected at field is required when collected on / collected by is present."],
            ],
            [
                "data" => [
                    "collected_at" => "1", "collected_by" => "1"
                ],
                "outcome" => [ "collected_on" => "The collected on field is required when collected at / collected by is present."],
            ],
            [
                "data" => [
                    "collected_at" => "1", "collected_on" => "invalid", "collected_by" => "1"
                ],
                "outcome" => [ "collected_on" => "The collected on does not match the format Y-m-d."],
            ],
            [
                "data" => [
                    "collected_at" => "9999", "collected_on" => "2018-07-21", "collected_by" => "1"
                ],
                "outcome" => [ "collected_at" => "The selected collected at is invalid."],
            ],
            [
                "data" => [
                    "collected_at" => "1", "collected_on" => "2018-07-21", "collected_by" => "9999"
                ],
                "outcome" => [ "collected_by" => "The selected collected by is invalid."],
            ],
        ];

        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $put_route = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        foreach ($dataSets as $set) {
            $response = $this->actingAs($this->centreUser, 'store')
                ->visit($route)
                ->put(
                    $put_route,
                    $set["data"]
                )
            ;
            // work out which field we're testing.
            $field = array_keys($set['outcome'])[0];

            // Dig out errors from Session
            $response->seeInSession('errors');
            $errors = Session::get("errors")->get($field);

            // Check our specific message is present
            $this->assertContains($set['outcome'][$field], $errors);

            // we follow that to the correct page;
            $this->followRedirects()
                ->seePageIs($route)
                ->assertResponseStatus(200)
            ;
        }
    }

    /** @test */
    public function testICanAddManyVouchers()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Add many vouchers;
        $this->actingAs($this->centreUser, 'store')
            ->post(
                $post_route,
                [
                    'start' => $this->testCodes[0],
                    'end' => $this->testCodes[count($this->testCodes)-1]
                ]
            );

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;
        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registration->currentBundle();

        // See that it's got many vouchers.
        $this->assertEquals(count($this->testCodes), $currentBundle->vouchers()->count());
    }

    /** @test */
    public function testICannotAddTooManyVouchersToABundle()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Get the maxAdd value currently 101;
        $overMaxAdd = config('arc.bundle_max_voucher_append')+1;

        // Make the range 1-101,
        $startCode = "BIG00001";
        $endCode = "BIG" . str_pad($overMaxAdd, 5, "0", STR_PAD_LEFT);
        $bigRange = Voucher::generateCodeRange($startCode, $endCode);
        $this->assertEquals($overMaxAdd, count($bigRange));

        // Create the vouchers for the range;
        Auth::login($this->centreUser);
        foreach ($bigRange as $testCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('dispatch');
        }
        Auth::logout();

        // Attempt to bind the vouchers to the bundle
        $response = $this->actingAs($this->centreUser, 'store')
            ->post(
                $post_route,
                [
                    'start' => $startCode,
                    'end' => $endCode,
                ]
            );

        // Confirm that we have supplied the appropriate error message to the session
        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Failed adding more than ' . config('arc.bundle_max_voucher_append') . ' vouchers/'
        ));

        // see we're redirected back
        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;

        // See we have no vouchers added.
        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registration->currentBundle();

        // See that it's got no vouchers.
        $this->assertEquals(0, $currentBundle->vouchers()->count());
    }

    /** @test */
    public function testICanAddSingleVouchers()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Add a voucher;
        $this->actingAs($this->centreUser, 'store')
            ->post($post_route, ['start' => $this->testCodes[0]]);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;

        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registration->currentBundle();

        // See that it's got one voucher.
        $this->assertEquals(1, $currentBundle->vouchers()->count());
    }


    /** @test */
    public function testICanDeleteTheCurrentBundle()
    {
        /** @var Bundle $currentBundle */
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        // Make some vouchers to bundle.
        $testCodes = [
            'TST0123455',
            'TST0123456',
            'TST0123457'
        ];
        foreach ($testCodes as $testCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('dispatch');
            $voucher->bundle()->associate($currentBundle)->save();
        }

        // there should be 3 vouchers!
        $this->assertEquals(count($testCodes), $currentBundle->vouchers()->count());

        // Stash vouchers for test later
        //$vouchers = $currentBundle->vouchers()->get();

        $delete_route = route(
            'store.registration.vouchers.delete',
            [
                'registration' => $this->registration->id,
            ]
        );

        // Hit the route with a delete request;
        $this->actingAs($this->centreUser, 'store')
            ->delete($delete_route)
        ;

        // refresh bundle
        $currentBundle->refresh();
        // See less vouchers
        $this->assertEquals(0, $currentBundle->vouchers()->count());

        //Check all vouchers have NULL bundle_id

        $vouchers = Voucher::whereIn('code', $testCodes)->get();
        foreach ($vouchers as $v) {
            $this->assertNull($v->bundle_id);
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
            'TST0123455',
            'TST0123456',
            'TST0123457'
        ];
        foreach ($testCodes as $testCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('dispatch');
            $voucher->bundle()->associate($currentBundle)->save();
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

    /** @test */
    public function testICanSyncAnArrayOfVouchers()
    {
        $put_route = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        // sync a voucher;
        $this->actingAs($this->centreUser, 'store')
            ->put($put_route, ['vouchers' => [$this->testCodes[0]]]);

        $currentBundle = $this->registration->currentBundle();
        $this->assertEquals(1, $currentBundle->vouchers()->count());

        // re-sync with 3 vouchers
        $this->actingAs($this->centreUser, 'store')
            ->put($put_route, ['vouchers' => $this->testCodes]);

        $currentBundle->refresh();
        $this->assertEquals(count($this->testCodes), $currentBundle->vouchers()->count());

        // sync without a voucher array AT ALL - does nothing.
        $this->actingAs($this->centreUser, 'store')
            ->put($put_route, []);

        $currentBundle->refresh();
        $this->assertEquals(count($this->testCodes), $currentBundle->vouchers()->count());

        // sync with only a single empty voucher string erases the vouchers.
        $this->assertEquals(3, $currentBundle->vouchers()->count());

        $this->actingAs($this->centreUser, 'store')
            ->put($put_route, ['vouchers' => [''] ]);

        $currentBundle->refresh();
        $this->assertEquals(0, $currentBundle->vouchers()->count());
    }

    /** @test */
    public function testICannotDisburseAnEmptyBundle()
    {
        // Setup bundle
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);

        // There should be one currentBundle with 3 vouchers
        $this->assertCount(1, $this->registration->bundles);

        // Create a sensible place to have collected it.
        $disbursementCentre = Auth::user()->centre->id;

        // Create a sensible date to have Collected on
        $disbursementDate = Carbon::now()->startOfWeek()->format("Y-m-d");

        // Find a carer for bundle
        $collectingCarer = $this->registration->family->carers->first()->id;

        // Array all that
        $data = [
            "collected_at" => $disbursementCentre,
            "collected_on" => $disbursementDate,
            "collected_by" => $collectingCarer
        ];

        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $put_route = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        // Attempt to submit
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->put(
                $put_route,
                $data
            );

        // Confirm that we have supplied the appropriate error message to the session
        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Action denied on empty bundle/'
        ));

        // Check the submission was a success
        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testICannotAddAVoucherAllocatedInACentreIHaveAccessTo()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Add a voucher to the registration's bundle
        $this->actingAs($this->centreUser, 'store')
        ->visit($route)
        ->post(
            $post_route,
            ["start" => $this->testCodes[0]]
        );

        // Add a second registration in the same centre
        $this->registrationTwo = factory(Registration::class)->create([
            'centre_id' => $this->centre->id
        ]);

        $route_2 = route('store.registration.voucher-manager', [ 'registration' => $this->registrationTwo->id ]);
        $post_route_2 = route('store.registration.vouchers.post', [ 'registration' => $this->registrationTwo->id ]);

        // Attempt to post the same voucher code into the second registration's bundle
        $response = $this->actingAs($this->centreUser, 'store')
        ->visit($route_2)
        ->post(
            $post_route_2,
            ["start" => $this->testCodes[0]]
        );

        // See we have no vouchers added.
        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registrationTwo->currentBundle();

        // See that it's got no vouchers.
        $this->assertEquals(0, $currentBundle->vouchers()->count());

        // Check the expected error message is in the session
        $response->seeInSession('error_messages');
        $entity = Family::getAlias($this->programme);
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '~These vouchers are currently allocated to a different ' . $entity . '. Click on the voucher number to view the other ' . $entity . '\'s record: <a href="' . $route . '">' . $this->testCodes[0] . '</a>~'
            ));

        // Check the expected error message is in the view
        $this->followRedirects()
            ->seeInElement('div[class="alert-message error"]', 'Click on the voucher number to view the other ' . $entity . '\'s record: <a href="' . $route . '">' . $this->testCodes[0] . '</a>');
    }

    /** @test */
    public function testICannotAddAVoucherAllocatedInACentreIDoNotHaveAccessTo()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Add a voucher to the registration's bundle
        $this->actingAs($this->centreUser, 'store')
        ->visit($route)
        ->post(
            $post_route,
            ["start" => $this->testCodes[1]]
        );

        // Create a second sponsor, centre and user
        $this->sponsor2 = factory(Sponsor::class)->create();

        $this->centre2 = factory(Centre::class)->create(["sponsor_id" => $this->sponsor2->id]);

        $this->centreUser2 = factory(CentreUser::class)->create([
            "name"  => "second test user",
            "email" => "testuser2@example.com",
            "password" => bcrypt('test_user_pass2'),
            "role" => "centre_user"
        ]);

        $this->centreUser2->centres()->attach($this->centre2->id, ['homeCentre' => true]);

        // Add a registration to the second centre
        $this->registrationTwo = factory(Registration::class)->create([
            'centre_id' => $this->centre2->id
        ]);

        $route_2 = route('store.registration.voucher-manager', [ 'registration' => $this->registrationTwo->id ]);
        $post_route_2 = route('store.registration.vouchers.post', [ 'registration' => $this->registrationTwo->id ]);

        // Attempt to post the same voucher code into the second registration's bundle
        $response = $this->actingAs($this->centreUser2, 'store')
        ->visit($route_2)
        ->post(
            $post_route_2,
            ["start" => $this->testCodes[1]]
        );

        // See we have no vouchers added.
        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registrationTwo->currentBundle();

        // See that it's got no vouchers.
        $this->assertEquals(0, $currentBundle->vouchers()->count());
        $entity = Family::getAlias($this->programme);
        // Check the expected error message is in the session
        $response->seeInSession('error_messages');
        //dd(Session::get('error_messages'));
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '~These vouchers are allocated to a different ' . $entity . ' in a centre you can\'t access: ' . $this->testCodes[1] . '~'
        ));


        // Check the expected error message is in the view
        $this->followRedirects()
        ->seeInElement('div[class="alert-message error"]', 'These vouchers are allocated to a different ' . $entity . ' in a centre you can\'t access: ' . $this->testCodes[1]);
    }

    /** @test */
    public function itCanAcceptAndCleanVouchersWithSpacesIn()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        // Create a voucherCode with a space in from the test list
        $voucherCode = $this->testCodes[0];
        $randPos = rand(0, strlen($voucherCode));
        $voucherCode = substr_replace($voucherCode, " ", $randPos, 0);

        // Add a voucher;
        $this->actingAs($this->centreUser, 'store')
            ->post($post_route, ['start' => $voucherCode]);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;

        /** @var Bundle $currentBundle */
        // Get our currentBundle
        $currentBundle = $this->registration->currentBundle();

        // See that it's got one voucher.
        $this->assertEquals(1, $currentBundle->vouchers()->count());
    }

    /** @test */
    public function itHasSparseFormDataCleanedBeforeProcessing()
    {
        $route = route('store.registration.voucher-manager', [ 'registration' => $this->registration->id ]);
        $post_route = route('store.registration.vouchers.post', [ 'registration' => $this->registration->id ]);

        $data_null_end = [
            'start' => $this->testCodes[0],
            'end' => null
        ];

        $data_blank_end = [
            'start' => $this->testCodes[1],
            'end' => ''
        ];

        // Add null end voucher;
        $this->actingAs($this->centreUser, 'store')
            ->post(
                $post_route,
                $data_null_end
            );

        // Expect to see last record is testCode[0]
        $last_voucher = $this->registration
            ->currentBundle()
            ->vouchers()
            ->orderByDesc('id')
            ->first();
        $this->assertEquals($this->testCodes[0], $last_voucher->code);

        // Add empty string end vouchers;
        $this->actingAs($this->centreUser, 'store')
            ->post(
                $post_route,
                $data_blank_end
            );

        // Expect to see last record is testCode[1]
        $last_voucher = $this->registration
            ->currentBundle()
            ->vouchers()
            ->orderByDesc('id')
            ->first();
        $this->assertEquals($this->testCodes[1], $last_voucher->code);
    }

    /**
     * Search the session's array of error messages for one that matches our regular expression.
     *
     * @param (string|array)[] $errorMessages array
     * @param string $regex
     * @return bool whether a matching message was found or not
     */
    private function hasMatchingErrorMessage($errorMessages, $regex) {
        foreach ($errorMessages as $error) {
            // If the error message is an array describing some HTML, extract the text, otherwise use as a string directly.
            $string = is_array($error) && array_key_exists('html', $error) ? $error['html'] : $error;
            if (preg_match($regex, $string)) {
                return true;
            }
        }
        return false;
    }
}

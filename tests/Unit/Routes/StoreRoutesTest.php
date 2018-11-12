<?php

namespace Tests\Unit\Routes;

use Auth;
use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;
use Tests\StoreTestCase;

class StoreRoutesTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var CentreUser $neighborUser */
    private $neighborUser;

    /** @var CentreUser $foreignUser */
    private $unrelatedUser;

    /** @var Centre $centre */
    private $centre;

    public function setUp()
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

        $this->neighborUser = factory(CentreUser::class)->create([
            "name"  => "test neighbor",
            "email" => "testneighbor@example.com",
            "password" => bcrypt('test_neighbor_pass'),
            "centre_id" => factory(Centre::class)->create([
                "sponsor_id" => $this->centre->sponsor->id
            ])->id
        ]);

        $this->unrelatedUser = factory(CentreUser::class)->create([
            "name"  => "test unrelated",
            "email" => "testunrelated@example.com",
            "password" => bcrypt('test_unrelated_pass'),
            "centre_id" => factory(Centre::class)->create([
                "sponsor_id" => factory(Sponsor::class)->create()->id
            ])
        ]);
    }

    /**
     * Verify login direct.
     *
     * @return void
     * @test
     */
    public function testLoginGuestRoute()
    {
        Auth::logout();
        $this->get(URL::route('store.login'))
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;
    }

    /**
     * Verify forgot password direct
     *
     * @return void
     * @test
     */
    public function testForgotPasswordGuestRoute()
    {
        Auth::logout();
        $this->get(URL::route('store.password.request'))
            ->seePageIs(URL::route('store.password.request'))
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testDashboardRouteGate()
    {
        $route = URL::route('store.dashboard');

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;
        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testSearchRouteGate()
    {

        $route = URL::route('store.registration.index');

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;
        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testViewRouteGate()
    {

        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);

        $route = URL::route('store.registration.edit', [ 'registration' => $registration->id ]);

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;
        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testUpdateRouteGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);

        $edit_route = URL::route('store.registration.edit', [ 'registration' => $registration->id ]);
        $login_route = URL::route('store.login');

        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($edit_route)
        ;

        // Need to find the pri_carer id
        $pri_carer = $registration->family->carers->shift();

        $this->type("changedByTest", "pri_carer[". $pri_carer->id ."]")
            ->press("Save Changes")
            ->seePageIs($edit_route)
            ->assertResponseStatus(200)
            ->seeElement("input[id=carer][value=changedByTest]")
        ;

        Auth::logout();

        $this->type("**blanked**", "pri_carer[". $pri_carer->id ."]")
            ->press("Save Changes")
            ->seePageIs($login_route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testVoucherManageRouteGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);

        $route = URL::route('store.registration.voucher-manager', [ 'registration' => $registration->id ]);

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;

        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;

        Auth::logout();
        // Your neighbor can get there logged in.
        $this->assertEquals(
            $this->neighborUser->centre->sponsor->id,
            $this->centreUser->centre->sponsor->id
        );

        $this->actingAs($this->neighborUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200)
        ;

        Auth::logout();
        // An unrelated User cannot get there logged in.
        $this->assertNotEquals(
            $this->unrelatedUser->centre->sponsor->id,
            $this->centreUser->centre->sponsor->id
        );

        try {
            $this->actingAs($this->unrelatedUser, 'store')
                ->visit($route)
                ;
        } catch (\Exception $e) {
                $this->assertContains("Received status code [403]", $e->getMessage());
        }
    }

    public function testVoucherManagerUpdateGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);

        $route = URL::route('store.registration.voucher-manager', [ 'registration' => $registration->id ]);
        $put_route = URL::route('store.registration.vouchers.put', [ 'registration' => $registration->id ]);

        // I can call a PUT on a page as a user
        Auth::logout();
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->call(
                'PUT',
                $put_route,
                ['vouchers' => []] // should erase the vouchers.
            );
        $this->assertResponseStatus(302);

        // I can call put on a page as a neighbor
        Auth::logout();
        $this->actingAs($this->neighborUser, 'store')
            ->visit($route)
            ->call(
                'PUT',
                $put_route,
                ['vouchers' => []] // should erase the vouchers.
            );
        $this->assertResponseStatus(302);

        Auth::logout();
        // An unrelated User cannot get there logged in.
        $this->assertNotEquals(
            $this->unrelatedUser->centre->sponsor->id,
            $this->centreUser->centre->sponsor->id
        );

        try {
            $this->actingAs($this->unrelatedUser, 'store')
                ->visit($route)
                ->call(
                    'PUT',
                    $put_route,
                    [] // should erase the vouchers.
                )
            ;
        } catch (\Exception $e) {
            $this->assertContains("Received status code [403]", $e->getMessage());
        }
    }

    /** test */
    public function testCentreRegistrationsSummaryGate()
    {
        // Create an FM User
        $fmuser =  factory(CentreUser::class)->create([
            "name"  => "FM test user",
            "email" => "testfmuser@example.com",
            "password" => bcrypt('test_fmuser_pass'),
            "centre_id" => $this->centre->id,
            "role" => "foodmatters_user",
        ]);

        // Create a CC user
        $ccuser =  factory(CentreUser::class)->create([
            "name"  => "CC test user",
            "email" => "testccuser@example.com",
            "password" => bcrypt('test_ccuser_pass'),
            "centre_id" => $this->centre->id,
            "role" => "centre_user",
        ]);

        // Make some registrations
        factory(Registration::class, 5)->create([
            "centre_id" => $this->centre->id,
        ]);

        $route = URL::route('store.centres.registrations.summary');

        Auth::logout();

        // Bounce unauth'd to login
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200)
        ;

        // Throw a 403 for auth'd but forbidden
        $this->actingAs($ccuser, 'store')
            // need to get, because visit() bombs out with exceptions before you can check them.
            ->get($route)
            ->assertResponseStatus(403)
        ;
        Auth::logout();

        // See page do interesting things
        $this->actingAs($fmuser, 'store')
            ->get($route)
            ->assertResponseOK()
        ;
    }

    /** @test */
    public function testRegistrationFamilyUpdateGate()
    {
    }
}

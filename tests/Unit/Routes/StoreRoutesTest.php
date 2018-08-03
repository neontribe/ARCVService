<?php

namespace Tests\Unit\Routes;

use Auth;
use App\Centre;
use App\CentreUser;
use App\Registration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;
use Tests\TestCase;

class StoreRoutesTest extends TestCase
{
    use DatabaseMigrations;

    /** @var CentreUser $user */
    private $centreUser;

    /** @var Centre $centre */
    private $centre;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a User
        $this->user =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
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
        $this->get(URL::route('service.login'))
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
        $this->get(URL::route('store.passwordrequest'))
            ->seePageIs(URL::route('store.passwordrequest'))
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
        $this->actingAs($this->centreUser)
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
        $this->actingAs($this->centreUser)
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
        $this->actingAs($this->centreUser)
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
        $this->actingAs($this->centreUser)
            ->visit($edit_route)
        ;

        $this->type("changedByTest", "carer")
            ->press("Save Changes")
            ->seePageIs($edit_route)
            ->assertResponseStatus(200)
            ->seeElement("input[value=changedByTest]")
        ;

        Auth::logout();

        $this->type("**blanked**", "carer")
            ->press("Save Changes")
            ->seePageIs($login_route)
            ->assertResponseStatus(200)
        ;
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
        $this->actingAs($ccuser)
            // need to get, because visit() bombs out with exceptions before you can check them.
            ->get($route)
            ->assertResponseStatus(403)
        ;
        Auth::logout();

        // See page do interesting things
        $this->actingAs($fmuser)
            ->visit($route)
            ->assertResponseOK()
        ;
    }

    /** @test */
    public function testRegistrationFamilyUpdateGate()
    {
    }
}

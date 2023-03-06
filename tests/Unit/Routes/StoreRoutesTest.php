<?php

namespace Tests\Unit\Routes;

use App\StateToken;
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

    /** @var CentreUser $fmUser */
    private $fmUser;

    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var CentreUser $downlaoderUser */
    private $downloaderUser;

    /** @var CentreUser $neighbourUser */
    private $neighbourUser;

    /** @var CentreUser $foreignUser */
    private $unrelatedUser;

    /** @var Centre $centre */
    private $centre;

    /** @var Centre $neighbourCentre */
    private $neighbourCentre;

    /** @var Centre $unrelatedCentre */
    private $unrelatedCentre;

    private $dashboardRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        $this->neighbourCentre = factory(Centre::class)->create([
            'sponsor_id' => $this->centre->sponsor->id
        ]);

        $this->unrelatedCentre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id
        ]);

        // Create a few Users
        $this->fmUser = factory(CentreUser::class)->state('FMUser')->create([
            'name' => 'FM test user',
            'email' => 'testfmuser@example.com',
            'password' => bcrypt('test_fmuser_pass'),
        ]);
        $this->fmUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->centreUser = factory(CentreUser::class)->create([
            'name' => 'test user',
            'email' => 'testuser@example.com',
            'password' => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->downloaderUser = factory(CentreUser::class)->state('withDownloader')->create([
            'name' => 'test downloader',
            'email' => 'testdl@example.com',
            'password' => bcrypt('test_user_pass'),
        ]);
        $this->downloaderUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->neighbourUser = factory(CentreUser::class)->create([
            'name' => 'test neighbour',
            'email' => 'testneighbour@example.com',
            'password' => bcrypt('test_neighbour_pass'),
        ]);
        $this->neighbourUser->centres()->attach($this->neighbourCentre->id, ['homeCentre' => true]);

        $this->unrelatedUser = factory(CentreUser::class)->create([
            'name' => 'test unrelated',
            'email' => 'testunrelated@example.com',
            'password' => bcrypt('test_unrelated_pass'),
        ]);
        $this->unrelatedUser->centres()->attach($this->unrelatedCentre->id, ['homeCentre' => true]);

        $this->dashboardRoute = URL::route('store.dashboard');
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
            ->assertResponseStatus(200);
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
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testDashboardRouteGate()
    {
        Auth::logout();
        // You cannot get there logged out.
        $this->visit($this->dashboardRoute)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);
        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboardRoute)
            ->seePageIs($this->dashboardRoute)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testSearchRouteGate()
    {
        $route = URL::route('store.registration.index');

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);
        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testEditRouteGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            'centre_id' => $this->neighbourCentre->id,
        ]);

        // Make the route;
        $route = URL::route('store.registration.edit', ['registration' => $registration->id]);

        // You cannot get there logged out.
        Auth::logout();

        $this->get($route)
            ->followRedirects()
            ->seePageIs(route('store.login'))
            ->assertResponseOK();

        // You can get there logged in.
        Auth::logout();
        $this->actingAs($this->centreUser, 'store')
            ->get($route)
            ->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testUpdateRouteGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        $edit_route = URL::route('store.registration.edit', ['registration' => $registration->id]);
        $login_route = URL::route('store.login');

        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($edit_route);

        // Need to find the pri_carer id
        $pri_carer = $registration->family->carers->shift();

        $this->type('changedByTest', 'pri_carer[' . $pri_carer->id . ']')
            ->press('Save Changes')
            ->seePageIs($edit_route)
            ->assertResponseStatus(200)
            ->seeElement('input[id=carer][value=changedByTest]');

        Auth::logout();

        $this->type('**blanked**', 'pri_carer[' . $pri_carer->id . ']')
            ->press('Save Changes')
            ->seePageIs($login_route)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testVoucherManageRouteGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.registration.voucher-manager', ['registration' => $registration->id]);

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        // You can get there logged in.
        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200);

        Auth::logout();
        // Your neighbour can get there logged in.
        $this->assertEquals(
            $this->neighbourUser->centre->sponsor->id,
            $this->centreUser->centre->sponsor->id
        );

        $this->actingAs($this->neighbourUser, 'store')
            ->visit($route)
            ->seePageIs($route)
            ->assertResponseStatus(200);

        Auth::logout();
        // An unrelated User cannot get there logged in.
        $this->assertNotEquals(
            $this->unrelatedUser->centre->sponsor->id,
            $this->centreUser->centre->sponsor->id
        );

        try {
            $this->actingAs($this->unrelatedUser, 'store')
                ->visit($route);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Received status code [403]', $e->getMessage());
        }
    }

    /** @test */
    public function testVoucherManagerUpdateGate()
    {
        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.registration.voucher-manager', ['registration' => $registration->id]);
        $put_route = URL::route('store.registration.vouchers.put', ['registration' => $registration->id]);

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

        // I can call put on a page as a neighbour
        Auth::logout();
        $this->actingAs($this->neighbourUser, 'store')
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
                );
        } catch (\Exception $e) {
            $this->assertStringContainsString('Received status code [403]', $e->getMessage());
        }
    }

    /** test */
    public function testCentresRegistrationsSummaryGate()
    {
        // Make some registrations
        factory(Registration::class, 5)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.centres.registrations.summary');

        Auth::logout();

        // Bounce unauth'd to login
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        // Throw a 403 for auth'd but forbidden
        $this->actingAs($this->centreUser, 'store')
            // need to get, because visit() bombs out with exceptions before you can check them.
            ->get($route)
            ->assertResponseStatus(403);
        Auth::logout();

        // Download user cannot access bulk route
        $this->actingAs($this->downloaderUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseStatus(403);

        Auth::logout();

        // See page do interesting things for an fm user
        $this->actingAs($this->fmUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();
    }

    /** test */
    public function testCentreRegistrationsSummaryGate()
    {
        // Make some registrations
        factory(Registration::class, 5)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.centre.registrations.summary', ['centre' => $this->centre->id]);

        Auth::logout();

        // Bounce unauth'd to login
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        // Throw a 403 for auth'd but forbidden
        $this->actingAs($this->centreUser, 'store')
            // need to get, because visit() bombs out with exceptions before you can check them.
            ->get($route)
            ->assertResponseStatus(403);

        Auth::logout();

        // Download user can access specific route
        $this->actingAs($this->downloaderUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();

        Auth::logout();

        // FM User can access specific route
        $this->actingAs($this->fmUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();
    }

    /** @test */
    public function testSessionUpdateGate()
    {
        $put_route = URL::route('store.session.put');

        $this->assertEquals(1, $this->centreUser->centres()->count());

        // add centres
        $centres = factory(Centre::class, 3)->create();
        $this->centreUser->centres()->attach($centres->pluck(['id'])->toArray());
        $this->centreUser->refresh();
        $this->assertEquals(4, $this->centreUser->centres()->count());

        // can only PUT to the route if logged in.
        Auth::logout();
        $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboardRoute)
            ->call(
                'PUT',
                $put_route,
                ['centre' => $centres->last()->id]
            );
        $this->assertResponseStatus(302);

        // return to prior route
        $this->followRedirects()
            ->seePageIs($this->dashboardRoute)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testRegistrationFamilyUpdateGate()
    {
        $registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.registration.family', $registration->family->id);

        Auth::logout();
        // You cannot get there logged out.
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        $this->actingAs($this->centreUser, 'store')
                ->visit($route)
                ->assertResponseStatus(200);

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $registration->id))
            ->call(
                'PUT',
                $route,
                ['leaving_reason' => 'found employment']
            );
        $this->assertResponseStatus(302);

        // I can call put on a page as a neighbour
        Auth::logout();
        $this->actingAs($this->neighbourUser, 'store')
            ->visit(URL::route('store.registration.edit', $registration->id))
            ->call(
                'PUT',
                $route,
                ['leaving_reason' => 'found employment']
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
                ->visit(URL::route('store.registration.edit', $registration->id))
                ->call(
                    'PUT',
                    $route,
                    ['leaving_reason' => 'found employment']
                );
        } catch (\Exception $e) {
            $this->assertStringContainsString('Received status code [403]', $e->getMessage());
        }
    }

    /** @test */
    public function testShowPaymentRequestRoute()
    {
        // Don't need to be logged in.
        Auth::logout();

        $stateToken = factory(StateToken::class)->create([
            'uuid' => '12345678-1234-1234-1234-1234567890ab'
        ]);

        // Make a route url with the uuid.
        $route = URL::route('store.payment-request.show', ['paymentUuid' => $stateToken->uuid]);

        // GET the page with a correct Uuid
        $this->get($route)
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testPayPaymentRequestRoute()
    {
        // Don't need to be logged in.
        Auth::logout();

        $stateToken = factory(StateToken::class)->create([
            'uuid' => '12345678-1234-1234-1234-1234567890ab'
        ]);

        // Make route urls with the uuid. Technically, they're the same route ut the controller should bounce to 302.
        $route_update = URL::route('store.payment-request.update', ['paymentUuid' => $stateToken->uuid]);
        $route_show = URL::route('store.payment-request.show', ['paymentUuid' => $stateToken->uuid]);

        // PUT the page with a correct Uuid - other than that it doesn't "need" any request params or body.
        $this->put($route_update)
            ->seePageIs($route_show)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function testMVLRouteGuard()
    {
        // Make some registrations
        factory(Registration::class, 5)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.vouchers.mvl.export');

        Auth::logout();

        // Bounce unauth'd to login
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        // Throw a 403 for auth'd but forbidden
        $this->actingAs($this->centreUser, 'store')
            // need to get, because visit() bombs out with exceptions before you can check them.
            ->get($route)
            ->assertResponseStatus(403);

        Auth::logout();

        // Throw a 403 for auth'd but forbidden
        $this->actingAs($this->downloaderUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseStatus(403);

        Auth::logout();

        // See page permit FM User to get from the dashboard.
        $this->actingAs($this->fmUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            // It's going to 302->200 if there's a problem finding/reading the file (which isn't there).
            // However, we need to know that it *can* hit the right route
            // Tests for *what gets returned are elsewhere.
            ->followRedirects()
            ->assertResponseOK();
    }

    /** @test */
    public function testCentreRegistrationCollectionGate()
    {
        factory(Registration::class, 5)->create([
            'centre_id' => $this->centre->id,
        ]);

        $route = URL::route('store.centre.registrations.collection', ['centre' => $this->centre->id]);

        Auth::logout();

        // Bounce unauth'd to login
        $this->visit($route)
            ->seePageIs(URL::route('store.login'))
            ->assertResponseStatus(200);

        // Centre User can print the collection forms
        $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();

        Auth::logout();

        // Download user can access specific route
        $this->actingAs($this->downloaderUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();

        Auth::logout();

        // FM User can access specific route
        $this->actingAs($this->fmUser, 'store')
            ->visit($this->dashboardRoute)
            ->get($route)
            ->assertResponseOK();
    }
}

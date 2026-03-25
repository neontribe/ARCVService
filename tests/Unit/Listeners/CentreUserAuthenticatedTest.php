<?php

namespace Tests\Unit\Listeners;

use App\Centre;
use App\CentreUser;
use App\Listeners\CentreUserAuthenticated;
use App\Sponsor;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CentreUserAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'CentreUserCurrentCentreId';

    private Sponsor $sponsor;

    private CentreUser $centreUser;

    private Centre $centre;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure session is available
        Session::start();
        Session::forget(self::KEY);

        $this->sponsor = factory(Sponsor::class)->create();
        $this->centre = factory(Centre::class)->create(['sponsor_id' => $this->sponsor->id]);

        $this->centreUser = factory(CentreUser::class)->create();
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    }


    public function testItDoesNothingForNonCentreUsers(): void
    {
        Config::set('arc.default_to_home_centre', true);

        $listener = new CentreUserAuthenticated();

        $nonCentreUser = new class () {
            public $homeCentre = null;
        };

        $listener->handle(new Authenticated('web', $nonCentreUser));

        $this->assertTrue(Session::missing(self::KEY));
    }


    public function testItSetsSessionToAllWhenConfigIsFalseAndKeyIsMissing(): void
    {
        Config::set('arc.default_to_home_centre', false);

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame('all', Session::get(self::KEY));
    }


    public function testItSetsSessionToHomeCentreIdWhenConfigIsTrueAndKeyIsMissing(): void
    {
        Config::set('arc.default_to_home_centre', true);

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame($this->centre->id, Session::get(self::KEY));
    }


    public function testItSetsSessionToNullWhenConfigIsTrueAndHomeCentreIsNull(): void
    {
        Config::set('arc.default_to_home_centre', true);

        // make a centreUser, don't set up a homeCentre
        $user = factory(CentreUser::class)->create();

        $listener = new CentreUserAuthenticated();
        $listener->handle(new Authenticated('store', $user));

        // exists but is set null
        $this->assertTrue(Session::exists(self::KEY));
        $this->assertNull(Session::get(self::KEY));
    }


    public function testItDoesNotOverrideExistingSessionValue(): void
    {
        Config::set('arc.default_to_home_centre', true);

        Session::put(self::KEY, 'existing');

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame('existing', Session::get(self::KEY));
    }
}

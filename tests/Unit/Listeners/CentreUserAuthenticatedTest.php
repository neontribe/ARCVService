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

        $this->centreUser = factory(CentreUser::Class)->create();
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    }

    /** @test */
    public function it_does_nothing_for_non_centre_users(): void
    {
        Config::set('arc.default_to_home_centre', true);

        $listener = new CentreUserAuthenticated();

        $nonCentreUser = new class {
            public $homeCentre = null;
        };

        $listener->handle(new Authenticated('web', $nonCentreUser));

        $this->assertTrue(Session::missing(self::KEY));
    }

    /** @test */
    public function it_sets_session_to_all_when_config_is_false_and_key_is_missing(): void
    {
        Config::set('arc.default_to_home_centre', false);

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame('all', Session::get(self::KEY));
    }

    /** @test */
    public function it_sets_session_to_home_centre_id_when_config_is_true_and_key_is_missing(): void
    {
        Config::set('arc.default_to_home_centre', true);

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame($this->centre->id, Session::get(self::KEY));
    }

    /** @test */
    public function it_sets_session_to_null_when_config_is_true_and_home_centre_is_null(): void
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

    /** @test */
    public function it_does_not_override_existing_session_value(): void
    {
        Config::set('arc.default_to_home_centre', true);

        Session::put(self::KEY, 'existing');

        $listener = new CentreUserAuthenticated();

        $user = $this->centreUser;

        $listener->handle(new Authenticated('store', $user));

        $this->assertSame('existing', Session::get(self::KEY));
    }
}

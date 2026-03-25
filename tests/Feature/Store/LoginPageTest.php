<?php

namespace Tests\Feature\Store;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Centre;
use App\CentreUser;
use URL;

class LoginPageTest extends StoreTestCase
{
    use RefreshDatabase;

    private $centreUser = null;
    private $centre = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Create a deleted CentreUser
        $this->deletedcu = factory(CentreUser::class)->create([
            'name' => "testman",
            'email' => "deletedtestman@test.co.uk",
            "password" => bcrypt('deleted_user_pass'),
            'deleted_at' => date("Y-m-d H:i:s")
        ]);
    }


    public function testItShowsALoginPageWhenRouted(): void
    {
        $this->visit(URL::route('store.login'))
            ->assertResponseStatus(200)
            ->assertResponseOK()
            ->seeInElement('title', 'Login')
        ;
    }


    public function testItDoesNotShowTheLoggedInUserDetails(): void
    {
        $this->visit(URL::route('store.login'))
            ->dontSee($this->centreUser->name)
            ->dontSee($this->centreUser->centre->name)
        ;
    }


    public function testItShowsAForgotPasswordLink(): void
    {
        $this->visit(URL::route('store.login'))
            ->see('href="' . route('store.password.request') . '"')
        ;
    }


    public function testItShowsAUsernameInputBox(): void
    {
        $this->visit(URL::route('store.login'))
            ->seeElement('input[id=email]')
        ;
    }


    public function testItShowsAPasswordInputBox(): void
    {
        $this->visit(URL::route('store.login'))
            ->seeElement('input[id=password]')
        ;
    }


    public function testItDoesNotShowTheAuthUserMastheadWithLogoutLink(): void
    {
        $this->visit(URL::route('store.login'))
            ->dontSee('href="' . route('store.login') . '"')
        ;
    }


    public function testItAllowsAValidUserToLogin(): void
    {
        $this->visit(URL::route('store.login'))
            ->type('testuser@example.com', 'email')
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.dashboard'))
        ;
    }


    public function testItForbidsAnInvalidUserToLogin(): void
    {
        $this->visit(URL::route('store.login'))
            ->type('notauser@example.com', 'email')
            ->type('bad_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('auth.failed'))
        ;
    }


    public function testItForbidsADeletedUserToLogin(): void
    {
        $this->visit(URL::route('store.login'))
            ->type($this->deletedcu->email, 'email')
            ->type('deleted_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('auth.failed'))
        ;
    }


    public function testItRequiresAPasswordToLogin(): void
    {
        $this->visit(URL::route('store.login'))
            ->type('testuser@example.com', 'email')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('validation.required', ['attribute' => "password"]));
        ;
    }


    public function testItRequiresAnEmailToLogin(): void
    {
        $this->visit(URL::route('store.login'))
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('validation.required', ['attribute' => "email"]));
    }

    public function testItShowsACookieNotice(): void
    {
        $this->visit(URL::route('store.login'))
            ->see('cookie-agree')
            ->see(config('arc.links.privacy_policy'))
            ->see('cookie-notice')
        ;
    }
}

<?php

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginPageTest extends StoreTestCase
{

    use DatabaseMigrations;

    private $centreUser = null;
    private $centre = null;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(App\Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(App\CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    }

    /** @test */
    public function itShowsALoginPageWhenRouted()
    {
        $this->visit(URL::route('store.login'))
            ->assertResponseStatus(200)
            ->assertResponseOK()
            ->seeInElement('title', 'Login')
        ;
    }

    /** @test */
    public function itDoesNotShowTheLoggedInUserDetails()
    {
        $this->visit(URL::route('store.login'))
            ->dontSee($this->centreUser->name)
            ->dontSee($this->centreUser->centre->name)
        ;
    }

    /** @test */
    public function itShowsAForgotPasswordLink()
    {
        $this->visit(URL::route('store.login'))
            ->see('href="'. route('store.password.request') .'"')
        ;
    }

    /** @test */
    public function itShowsAUsernameInputBox()
    {
        $this->visit(URL::route('store.login'))
            ->seeElement('input[id=email]')
        ;
    }

    /** @test */
    public function itShowsAPasswordInputBox()
    {
        $this->visit(URL::route('store.login'))
            ->seeElement('input[id=password]')
        ;
    }

    /** @test */
    public function itDoesNotShowTheAuthUserMastheadWithLogoutLink()
    {
        $this->visit(URL::route('store.login'))
            ->dontSee('href="'. route('store.login') .'"')
        ;
    }

    /** @test */
    public function itAllowsAValidUserToLogin()
    {
        $this->visit(URL::route('store.login'))
            ->type('testuser@example.com', 'email')
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.dashboard'))
        ;
    }

    /** @test */
    public function itForbidsAnInvalidUserToLogin()
    {
        $this->visit(URL::route('store.login'))
            ->type('notauser@example.com', 'email')
            ->type('bad_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('auth.failed'))
        ;
    }

    /** @test */
    public function itRequiresAPasswordToLogin()
    {
        $this->visit(URL::route('store.login'))
            ->type('testuser@example.com', 'email')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('validation.required', ['attribute' => "password"]));
        ;
    }

    /** @test */
    public function itRequiresAnEmailToLogin()
    {
        $this->visit(URL::route('store.login'))
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('store.login'))
            ->see(trans('validation.required', ['attribute' => "email"]));
        ;
    }

    public function itShowsACookieWarning()
    {
        $this->visit(URL::route('store.login'))
            ->see('cookie.agree')
            ->see(config('arc.links.privacy_policy'))
            ->see('cookie-warning')
            ;
    }
}

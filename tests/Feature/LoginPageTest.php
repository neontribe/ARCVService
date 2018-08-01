<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginPageTest extends TestCase
{

    use DatabaseMigrations;

    private $user = null;
    private $centre = null;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(App\Centre::class)->create();

        // Create a User
        $this->user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itShowsALoginPageWhenRouted()
    {
        $this->visit(URL::route('service.login'))
            ->assertResponseStatus(200)
            ->assertResponseOK()
            ->seeInElement('title', 'Login')
        ;
    }

    /** @test */
    public function itDoesNotShowTheLoggedInUserDetails()
    {
        $this->visit(URL::route('service.login'))
            ->dontSee($this->user->name)
            ->dontSee($this->user->centre->name)
        ;
    }

    /** @test */
    public function itShowsAForgotPasswordLink()
    {
        $this->visit(URL::route('service.login'))
            ->see('href="'. route('password.request') .'"')
        ;
    }

    /** @test */
    public function itShowsAUsernameInputBox()
    {
        $this->visit(URL::route('service.login'))
            ->seeElement('input[id=email]')
        ;
    }

    /** @test */
    public function itShowsAPasswordInputBox()
    {
        $this->visit(URL::route('service.login'))
            ->seeElement('input[id=password]')
        ;
    }

    /** @test */
    public function itDoesNotShowTheAuthUserMastheadWithLogoutLink()
    {
        $this->visit(URL::route('service.login'))
            ->dontSee('href="'. route('service.login') .'"')
        ;
    }

    /** @test */
    public function itAllowsAValidUserToLogin()
    {
        $this->visit(URL::route('service.login'))
            ->type('testuser@example.com', 'email')
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('service.dashboard'))
        ;
    }

    /** @test */
    public function itForbidsAnInvalidUserToLogin()
    {
        $this->visit(URL::route('service.login'))
            ->type('notauser@example.com', 'email')
            ->type('bad_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('service.login'))
            ->see(trans('auth.failed'))
        ;
    }

    /** @test */
    public function itRequiresAPasswordToLogin()
    {
        $this->visit(URL::route('service.login'))
            ->type('testuser@example.com', 'email')
            ->press('Log In')
            ->seePageIs(URL::route('service.login'))
            ->see(trans('validation.required', ['attribute' => "password"]));
        ;
    }

    /** @test */
    public function itRequiresAnEmailToLogin()
    {
        $this->visit(URL::route('service.login'))
            ->type('test_user_pass', 'password')
            ->press('Log In')
            ->seePageIs(URL::route('service.login'))
            ->see(trans('validation.required', ['attribute' => "email"]));
        ;
    }
}

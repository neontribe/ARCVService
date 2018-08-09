<?php

use App\Centre;
use App\CentreUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spinen\MailAssertions\MailTracking;
use Tests\StoreTestCase;

class ForgotPasswordPageTest extends StoreTestCase
{
    use DatabaseMigrations;
    use MailTracking;


    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     */
    private $centre;
    private $centreUser;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itHasAnEmailInput()
    {
        $this->visit(route('store.password.request'))
            ->seeElement('input[type=email][name=email]')
        ;
    }

    /** @test */
    public function itHasASubmitButton()
    {
        $this->visit(route('store.password.request'))
            ->seeElement('button[type=submit]')
            ->seeInElement('button[type=submit]', 'Send Password Reset Link')
        ;
    }

    /** @test */
    public function itResetsPasswordForUserByEmailResetLink()
    {
        $this->visit(route('store.password.request'))
            ->type('testuser@example.com', 'email')
            ->press('Send Password Reset Link');
        $this->seeEmailWasSent()
            ->seeEmailSubjectEquals('Password Reset Request Notification')
            ->seeEmailContains('password/reset')
            ->seeEmailTo('testuser@example.com');
    }

    /** @test */
    public function itCannotEffectRedirectWithAManipulatedRefererHeader()
    {
        $this->visit(route('store.password.request'))
            ->see('Reset Password');

        $post_data = [
            'email' => 'testuser@example.com',
            '_token' => session('_token'),
        ];

        $headers = ['Referer' => 'www.google.com'];

        // Post, emulate clicking form button.
        $this->post('/password/email', $post_data, $headers);

        // Expecting to *not* be at google.
        $this->dontSee('www.google.com')
            ->see('/password/reset');
    }
}
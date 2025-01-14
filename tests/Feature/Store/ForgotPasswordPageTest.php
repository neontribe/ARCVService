<?php
namespace Tests\Feature\Store;

use App\Centre;
use App\CentreUser;
use App\Notifications\StorePasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\StoreTestCase;

class ForgotPasswordPageTest extends StoreTestCase
{
    use RefreshDatabase;


    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     */
    private $centre;
    private $centreUser;

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
        Notification::fake();

        $this->visit(route('store.password.request'))
            ->type('testuser@example.com', 'email')
            ->press('Send Password Reset Link');

        $user = $this->centreUser;
        Notification::assertSentTo(
            $user,
            StorePasswordResetNotification::class,
            function ($notification, $channels) use ($user) {
                $mailData = $notification->toMail($user);
                $this->assertStringContainsString('Password Reset Request Notification', $mailData->subject);
                $this->assertStringContainsString('password/reset', $mailData->actionUrl);
                return true;
            }
        );
    }

    /** @test */
    public function itCannotEffectRedirectWithAManipulatedRefererHeader()
    {
        Mail::fake();

        $this->visit(route('store.password.request'))
            ->see('Reset Password');

        $post_data = [
            'email' => 'testuser@example.com',
            '_token' => session('_token'),
        ];

        $headers = ['Referer' => 'www.google.com'];

        // Post, emulate clicking form button.
        $this->post(route('store.password.email'), $post_data, $headers)->followRedirects();
        $this->seeRouteIs('store.password.request');
    }
}

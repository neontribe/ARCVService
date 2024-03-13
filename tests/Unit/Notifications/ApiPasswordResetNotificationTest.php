<?php

namespace Tests\Unit\Notifications;

use App\Notifications\AdminPasswordResetNotification;
use App\Notifications\ApiPasswordResetNotification;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Notifications\Messages\MailMessage;
use Mockery\Mock;
use Tests\CreatesApplication;

class ApiPasswordResetNotificationTest extends TestCase
{
    use CreatesApplication;

    private string $token = "MY_SECRET_TOKEN";
    private string $name = "Buck Rogers";

    public function testToMail()
    {
        $notifiable = new MockNotification("wilma@example.com");

        $aprn = new ApiPasswordResetNotification($this->token, $this->name);
        $result = $aprn->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $result);
    }

    public function testToArray()
    {
        $notifiable = new Mock();

        $aprn = new ApiPasswordResetNotification($this->token, $this->name);
        $result = $aprn->toArray($notifiable);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testVia()
    {
        $notifiable = new Mock();

        $aprn = new ApiPasswordResetNotification($this->token, $this->name);
        $result = $aprn->via($notifiable);

        $this->assertIsArray($result);
        $this->assertEquals(['mail'], $result);
    }
}

class MockNotification
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }
}

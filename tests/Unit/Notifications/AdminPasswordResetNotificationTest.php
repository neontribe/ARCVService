<?php

namespace Tests\Unit\Notifications;

use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Notifications\Messages\MailMessage;
use Mockery\Mock;
use Tests\CreatesApplication;

class AdminPasswordResetNotificationTest extends TestCase
{
    use CreatesApplication;

    private string $token = "MY_SECRET_TOKEN";
    private string $name = "Buck Rogers";

    public function testToMail()
    {
        $notifiable = new Mock();

        $aprn = new AdminPasswordResetNotification($this->token, $this->name);
        $result = $aprn->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $result);
    }

    public function testToArray()
    {
        $notifiable = new Mock();

        $aprn = new AdminPasswordResetNotification($this->token, $this->name);
        $result = $aprn->toArray($notifiable);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testVia()
    {
        $notifiable = new Mock();

        $aprn = new AdminPasswordResetNotification($this->token, $this->name);
        $result = $aprn->via($notifiable);

        $this->assertIsArray($result);
        $this->assertEquals(['mail'], $result);
    }
}

<?php

namespace Tests\Unit\Models;

use App\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserModelTest extends TestCase
{
    use DatabaseMigrations;

    public function testSendPasswordResetNotification()
    {
        Notification::fake();
        $adminUsers = factory(AdminUser::class, 1)->create();
        $adminUsers[0]->sendPasswordResetNotification("A1B2C3");
        Notification::assertCount(1);
    }
}

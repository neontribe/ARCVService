<?php

namespace Tests\Unit\Routes;

use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ServiceRoutesTest extends TestCase
{
    use DatabaseMigrations;

    public function testServiceRoute()
    {
        $admin = factory(\App\AdminUser::class)->create();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect('/')
        ;
    }

    /**
     * Verify content on dashboard.
     *
     * @return void
     */
    public function testLoginRoute()
    {
        $this->get(route('admin.login'))
            ->assertStatus(200)
        ;
    }
}

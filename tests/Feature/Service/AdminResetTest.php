<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Laravel\BrowserKitTesting\TestCase;
use Mockery;
use Tests\CreatesApplication;
use Symfony\Component\Process\Process;

class AdminResetTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesApplication;

    /** @var AdminUser $adminUser */
    private $adminUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminUser = factory(AdminUser::class)->create();
    }

    public function testResetRoute()
    {
        // Mock the Process command
        $mockProcess = Mockery::mock(Process::class);
        $this->app->instance(Process::class, $mockProcess);
        // Mock DB
        DB::shouldReceive('table->where->pluck')
        ->once()
        ->with("secret")
        ->andReturn(["foo"]);

        // Run the test
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('data.reset'))
            // ->followRedirects()
            ->assertResponseStatus(302);
    }
}
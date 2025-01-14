<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\BrowserKitTesting\TestCase;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\CreatesApplication;

class AdminResetTest extends TestCase
{
    use RefreshDatabase;
    use CreatesApplication;

    /** @var AdminUser $adminUser */
    private AdminUser $adminUser;

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
        DB::table('oauth_clients')->insert(
            array(
                'id' => 2,
                'user_id' => 555,
                'name' => "0",
                'secret' => "0",
                'provider' => "0",
                'redirect' => "0",
                'personal_access_client' => 3,
                'password_client' => 4,
                'revoked' => 5,
                'created_at' => "0",
                'updated_at' => "0",
            )
        );
        // Mock DB - DOES NOT WORK. I think the Process call spawns a new thread
//        $mockDb = Mockery::mock(DatabaseManager::class, );
//        $mockDb->shouldReceive('table->where->pluck')
//        ->once()
//        ->with("secret")
//        ->andReturn([
//            [
//                'id' => 1,
//                'userId' => 555,
//                'name' => "0",
//                'secret' => "0",
//                'provider' => "0",
//                'redirect' => "0",
//                'personal_access_client' => 3,
//                'password_client' => 4,
//                'revoked' => 5,
//                'created_at' => "0",
//                'updated_at' => "0",
//            ]
//        ]);
//        $cls = get_class(DB::getFacadeRoot());

        // Run the test
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('data.reset'))
            ->assertResponseStatus(302);
    }
}

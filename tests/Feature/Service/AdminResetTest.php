<?php

namespace Feature\Service;

use App\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class AdminResetTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = factory(AdminUser::class)->create();
    }

    private function makeRequest(): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('data.reset'));
    }

    /**
     * Mocks the DB::table('oauth_clients') chain used to retrieve the new
     * passport secret after the seed. Avoids a dependency on the oauth_clients
     * table existing in the test schema.
     */
    private function mockOauthClientQuery(string $secret = 'new-test-secret'): void
    {
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $builder->shouldReceive('pluck')->with('secret')->andReturn(collect([$secret]));

        DB::shouldReceive('table')->with('oauth_clients')->andReturn($builder);
    }

    public function testRedirectsUnauthenticatedUser(): void
    {
        $response = $this->get(route('data.reset'));

        $response->assertRedirect();
    }

    public function testUnauthenticatedRequestDoesNotReachGateCheck(): void
    {
        Gate::shouldReceive('allows')->never();

        $this->get(route('data.reset'));
    }

    public function testRedirectsToDashboardWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);

        $this->makeRequest()->assertRedirect(route('admin.dashboard'));
    }

    public function testFlashesErrorWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);

        $this->makeRequest()->assertSessionHas('error', 'Action Denied');
    }

    public function testDoesNotFlashSuccessMessageWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);

        $this->makeRequest()->assertSessionMissing('message');
    }

    public function testRedirectsToDashboardWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertRedirect(route('admin.dashboard'));
    }

    public function testFlashesSuccessMessageWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertSessionHas('message');
    }

    public function testSuccessMessageContainsReseededText(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();

        $message = $this->makeRequest()->getSession()->get('message');

        $this->assertStringContainsString('Reseeded', $message);
    }

    public function testDoesNotFlashErrorMessageWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertSessionMissing('error');
    }

    public function testRunsMigrateRefreshWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:refresh', ['--seed' => true, '--force' => true]);
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', Mockery::any());

        $this->makeRequest();
    }

    public function testCreatesPassportClientWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockOauthClientQuery();
        Artisan::shouldReceive('call')->once()->with('migrate:refresh', Mockery::any());
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', [
                '--password' => true,
                '--name'     => 'Rose Vouchers Password Grant Client',
                '--provider' => 'users',
            ]);

        $this->makeRequest();
    }

    public function testArtisanIsNotCalledWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);
        Artisan::shouldReceive('call')->never();

        $this->makeRequest();
    }
}

<?php

namespace Feature\Service;

use App\AdminUser;
use App\Services\EnvWriter;
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
        // don't write to our env file!
        $this->mock(EnvWriter::class)
            ->shouldReceive('updateKey')
            ->andReturnNull();

        $this->admin = factory(AdminUser::class)->create();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeRequest(): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('data.reset'));
    }

    /**
     * Prevents Artisan commands from actually executing in gate-allowed tests
     * that aren't specifically asserting on Artisan behaviour.
     * Must be called before mockOauthClientQuery() to avoid DB facade conflicts.
     */
    private function mockArtisan(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
    }

    /**
     * Mocks the DB::table('oauth_clients') chain. Call this after mockArtisan()
     * so the DB facade is only locked down once Artisan is already stubbed out.
     */
    private function mockOauthClientQuery(string $secret = 'new-test-secret'): void
    {
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $builder->shouldReceive('pluck')->with('secret')->andReturn(collect([$secret]));

        DB::shouldReceive('table')->with('oauth_clients')->andReturn($builder);
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function testRedirectsUnauthenticatedUser(): void
    {
        $this->get(route('data.reset'))->assertRedirect();
    }

    public function testUnauthenticatedRequestDoesNotReachGateCheck(): void
    {
        Gate::shouldReceive('allows')->never();

        $this->get(route('data.reset'));
    }

    // -------------------------------------------------------------------------
    // Gate denied
    // -------------------------------------------------------------------------

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

    public function testArtisanIsNotCalledWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);
        Artisan::shouldReceive('call')->never();

        $this->makeRequest();
    }

    // -------------------------------------------------------------------------
    // Gate allowed
    // -------------------------------------------------------------------------

    public function testRedirectsToDashboardWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockArtisan();
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertRedirect(route('admin.dashboard'));
    }

    public function testFlashesSuccessMessageWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockArtisan();
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertSessionHas('message');
    }

    public function testSuccessMessageContainsReseededText(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockArtisan();
        $this->mockOauthClientQuery();

        // assertSessionHas with a closure replaces the missing getSession() proxy
        $this->makeRequest()->assertSessionHas(
            'message',
            function (string $value) {
                return str_contains($value, 'Reseeded');
            }
        );
    }

    public function testDoesNotFlashErrorMessageWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        $this->mockArtisan();
        $this->mockOauthClientQuery();

        $this->makeRequest()->assertSessionMissing('error');
    }

    public function testRunsMigrateRefreshWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        // Fine-grained expectations — set up Artisan before DB
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:refresh', ['--seed' => true, '--force' => true]);
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', Mockery::any());
        $this->mockOauthClientQuery();

        $this->makeRequest();
    }

    public function testCreatesPassportClientWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);
        // Fine-grained expectations — set up Artisan before DB
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:refresh', Mockery::any());
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', [
                '--password' => true,
                '--name'     => 'Rose Vouchers Password Grant Client',
                '--provider' => 'users',
            ]);
        $this->mockOauthClientQuery();

        $this->makeRequest();
    }
}

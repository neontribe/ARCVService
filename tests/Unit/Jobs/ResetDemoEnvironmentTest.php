<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ResetDemoEnvironment;
use App\Services\EnvWriter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ResetDemoEnvironmentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function mockOauthClientQuery(string $secret = 'new-test-secret'): void
    {
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $builder->shouldReceive('pluck')->with('secret')->andReturn(collect([$secret]));

        DB::shouldReceive('table')->with('oauth_clients')->andReturn($builder);
    }

    private function mockEnvWriter(): MockInterface
    {
        return $this->mock(EnvWriter::class);
    }

    private function dispatchJob(): void
    {
        app(ResetDemoEnvironment::class)->handle(app(EnvWriter::class));
    }

    // -------------------------------------------------------------------------
    // Artisan commands
    // -------------------------------------------------------------------------

    public function testRunsMigrateRefresh(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:refresh', ['--seed' => true, '--force' => true]);
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', Mockery::any());
        $this->mockEnvWriter()->shouldReceive('updateKey')->andReturnNull();
        $this->mockOauthClientQuery();

        $this->dispatchJob();
    }

    public function testCreatesPassportClientWithCorrectArguments(): void
    {
        Artisan::shouldReceive('call')->once()->with('migrate:refresh', Mockery::any());
        Artisan::shouldReceive('call')
            ->once()
            ->with('passport:client', [
                '--password' => true,
                '--name' => 'Rose Vouchers Password Grant Client',
                '--provider' => 'users',
            ]);
        $this->mockEnvWriter()->shouldReceive('updateKey')->andReturnNull();
        $this->mockOauthClientQuery();

        $this->dispatchJob();
    }

    // -------------------------------------------------------------------------
    // Secret retrieval
    // -------------------------------------------------------------------------

    public function testQueriesOauthClientsForNewSecret(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockEnvWriter()->shouldReceive('updateKey')->andReturnNull();

        $builder = Mockery::mock();
        $builder->shouldReceive('where')->once()->with('id', 1)->andReturnSelf();
        $builder->shouldReceive('pluck')->once()->with('secret')->andReturn(collect(['expected-secret']));
        DB::shouldReceive('table')->once()->with('oauth_clients')->andReturn($builder);

        $this->dispatchJob();
    }

    // -------------------------------------------------------------------------
    // EnvWriter
    // -------------------------------------------------------------------------

    public function testWritesNewSecretToEnvFile(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockOauthClientQuery('brand-new-secret');

        $this->mockEnvWriter()
            ->shouldReceive('updateKey')
            ->once()
            ->with('PASSWORD_CLIENT_SECRET', 'brand-new-secret');

        $this->dispatchJob();
    }

    public function testWritesToCorrectEnvKey(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockOauthClientQuery();

        $this->mockEnvWriter()
            ->shouldReceive('updateKey')
            ->once()
            ->with('PASSWORD_CLIENT_SECRET', Mockery::any());

        $this->dispatchJob();
    }
}

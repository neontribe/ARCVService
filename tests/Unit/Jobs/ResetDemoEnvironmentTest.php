<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ResetDemoEnvironment;
use App\Services\EnvWriter;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ResetDemoEnvironmentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function mockEnvWriter(): MockInterface
    {
        return $this->mock(EnvWriter::class);
    }

    private function mockClientRepository(string $secret = 'new-test-secret'): MockInterface
    {
        $client = Mockery::mock(Client::class);
        $client->plainSecret = $secret;

        $repository = $this->mock(ClientRepository::class);
        $repository->shouldReceive('createPasswordGrantClient')
            ->andReturn($client);

        return $repository;
    }

    private function dispatchJob(): void
    {
        app(ResetDemoEnvironment::class)->handle(
            app(EnvWriter::class),
            app(ClientRepository::class),
        );
    }

    // -------------------------------------------------------------------------
    // Artisan commands
    // -------------------------------------------------------------------------

    public function testRunsMigrateRefresh(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:refresh', ['--seed' => true, '--force' => true]);
        $this->mockEnvWriter()->shouldReceive('updateKey')->andReturnNull();
        $this->mockClientRepository();

        $this->dispatchJob();
    }

    // -------------------------------------------------------------------------
    // Passport client creation
    // -------------------------------------------------------------------------

    public function testCreatesPasswordGrantClientWithCorrectArguments(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockEnvWriter()->shouldReceive('updateKey')->andReturnNull();

        $this->mock(ClientRepository::class)
            ->shouldReceive('createPasswordGrantClient')
            ->once()
            ->with(null, 'Rose Vouchers Password Grant Client', '', 'users')
            ->andReturn(tap(Mockery::mock(Client::class), function ($c) {
                $c->plainSecret = 'test-secret';
            }));

        $this->dispatchJob();
    }

    // -------------------------------------------------------------------------
    // EnvWriter
    // -------------------------------------------------------------------------

    public function testWritesClientSecretToEnvFile(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockClientRepository('brand-new-secret');

        $this->mockEnvWriter()
            ->shouldReceive('updateKey')
            ->once()
            ->with('PASSWORD_CLIENT_SECRET', 'brand-new-secret');

        $this->dispatchJob();
    }

    public function testWritesToCorrectEnvKey(): void
    {
        Artisan::shouldReceive('call')->andReturn(0);
        $this->mockClientRepository();

        $this->mockEnvWriter()
            ->shouldReceive('updateKey')
            ->once()
            ->with('PASSWORD_CLIENT_SECRET', Mockery::any());

        $this->dispatchJob();
    }
}

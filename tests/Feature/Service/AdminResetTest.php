<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Jobs\ResetDemoEnvironment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminResetTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
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

    public function testUnauthenticatedRequestDoesNotDispatchJob(): void
    {
        $this->get(route('data.reset'));

        Bus::assertNothingDispatched();
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

    public function testDoesNotDispatchJobWhenGateDenies(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(false);

        $this->makeRequest();

        Bus::assertNothingDispatched();
    }

    // -------------------------------------------------------------------------
    // Gate allowed
    // -------------------------------------------------------------------------

    public function testRedirectsToDashboardWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);

        $this->makeRequest()->assertRedirect(route('admin.dashboard'));
    }

    public function testFlashesQueuedMessageWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);

        $this->makeRequest()->assertSessionHas('message', 'Reset queued');
    }

    public function testDoesNotFlashErrorWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);

        $this->makeRequest()->assertSessionMissing('error');
    }

    public function testDispatchesResetJobWhenGateAllows(): void
    {
        Gate::shouldReceive('allows')->with('take-developer-actions')->andReturn(true);

        $this->makeRequest();

        Bus::assertDispatched(ResetDemoEnvironment::class);
    }
}

<?php

namespace Tests\Unit\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class KernelTest extends TestCase
{
    use CreatesApplication;
    /**
     * @throws BindingResolutionException
     */
    public function testSchedule(): void
    {
        app()->make(Schedule::class);
        $this->artisan('schedule:list')
            ->assertExitCode(0)
            ->expectsOutputToContain("php artisan arc:createMVLReport");
    }
}

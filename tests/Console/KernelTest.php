<?php

namespace Tests\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\TestCase;

class KernelTest extends TestCase
{

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

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

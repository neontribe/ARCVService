<?php

namespace Tests\Console;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\TestCase;

class KernelTest extends TestCase
{

    public function createApplication(): Kernel
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function testSchedule(): void
    {
        $schedule = app()->make(Schedule::class);
        $this->assertTrue($schedule->hasCommand('arc:createMVLReport'));
    }
}

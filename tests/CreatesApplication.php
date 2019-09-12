<?php

namespace Tests;

use Hash;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        ini_set('max_execution_time', 600);

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        /*
         * `bcrypt` is intentionally a slow hash, for security.
         * `bcrypt` can be made faster in testing environments where hardy security isn't necessary.
         *
         * This line reduces the duration of tests by ~30% on my machine.
         */
        Hash::setRounds(4);

        return $app;
    }
}

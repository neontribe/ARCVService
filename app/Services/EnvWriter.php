<?php

namespace App\Services;

class EnvWriter
{
    public function __construct(private readonly string $envPath)
    {
    }

    public function updateKey(string $key, string $newValue): void
    {
        $contents = file_get_contents($this->envPath);

        file_put_contents($this->envPath, preg_replace(
            "/^{$key}=.*/m",
            "{$key}={$newValue}",
            $contents
        ));
    }
}

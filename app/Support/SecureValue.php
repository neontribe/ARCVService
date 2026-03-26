<?php

namespace App\Support;

use JsonSerializable;
use Stringable;

class SecureValue implements JsonSerializable, Stringable
{
    protected string $value;

    protected bool $revealed = false;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function reveal(): string
    {
        $this->revealed = true;

        return $this->value;
    }

    public function masked(int $visible = 4): string
    {
        $len = strlen($this->value);

        return str_repeat('*', $len - $visible) . substr($this->value, -$visible);
    }

    public function jsonSerialize(): mixed
    {
        return '[secret]';
    }

    public function __toString(): string
    {
        return '[secret]';
    }

    public function __debugInfo(): array
    {
        return ['secret' => '[hidden]'];
    }
}

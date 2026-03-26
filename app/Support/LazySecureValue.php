<?php

namespace App\Support;

use JsonSerializable;
use Stringable;

class LazySecureValue implements JsonSerializable, Stringable
{
    public function __construct(
        protected object $model,
        protected string $field,
    ) {
    }

    public function reveal(): mixed
    {
        if (! method_exists($this->model, 'authorizeReveal')) {
            throw new \LogicException(sprintf(
                '%s must define authorizeReveal()',
                $this->model::class
            ));
        }

        $this->model->authorizeReveal($this->field);

        $row = $this->model->decryptEncryptedRowForLazyAccess();

        return $row[$this->field] ?? null;
    }

    public function masked(int $visible = 4, string $mask = '*'): string
    {
        $value = $this->reveal();

        if ($value === null) {
            return '';
        }

        $value = (string) $value;
        $length = mb_strlen($value);

        if ($visible <= 0) {
            return str_repeat($mask, $length);
        }

        if ($length <= $visible) {
            return $value;
        }

        return str_repeat($mask, $length - $visible) . mb_substr($value, -$visible);
    }

    public function isNull(): bool
    {
        return $this->reveal() === null;
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
        return [
            'secret' => '[hidden]',
            'field' => $this->field,
            'model' => $this->model::class,
        ];
    }
}

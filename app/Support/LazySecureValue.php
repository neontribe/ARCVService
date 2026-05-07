<?php

namespace App\Support;

use JsonSerializable;
use Stringable;

class LazySecureValue implements JsonSerializable, Stringable
{
    public function __construct(
        protected LazySecureModel $model,
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


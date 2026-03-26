<?php

namespace App\Support;

use JsonSerializable;
use Stringable;

class LazySecureValue implements JsonSerializable, Stringable
{
    protected object $model;
    protected string $field;
    protected ?string $plaintext = null;

    public function __construct(object $model, string $field)
    {
        $this->model = $model;
        $this->field = $field;
    }

    public function reveal(): ?string
    {
        if ($this->plaintext !== null) {
            return $this->plaintext;
        }

        $row = $this->model::getCipherSweetEncryptedRow();

        $payload = [];

        foreach ($row->listEncryptedFields() as $field) {
            $payload[$field] = $this->model->getRawOriginal($field);
        }

        $decrypted = $row
            ->setPermitEmpty(config('ciphersweet.permit_empty', false))
            ->decryptRow($payload);

        return $this->plaintext = $decrypted[$this->field] ?? null;
    }

    public function masked(int $visible = 4): string
    {
        $value = $this->reveal();

        if ($value === null) {
            return '';
        }

        return str_repeat('*', strlen($value) - $visible)
            . substr($value, -$visible);
    }

    public function __toString()
    {
        return '[secret]';
    }

    public function jsonSerialize(): mixed
    {
        return '[secret]';
    }

    public function __debugInfo()
    {
        return ['secret' => '[lazy]'];
    }
}

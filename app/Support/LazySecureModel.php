<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

abstract class LazySecureModel extends Model
{
    use UsesCipherSweetLazy;

    protected bool $secretsHidden = true;
    protected static array $encryptedFieldCache = [];

    /** when retrieving the model  auto-"hide" the fields */
    protected static function booted(): void
    {
        static::retrieved(static function (self $model) {
            $model->hideEncryptedAttributes();
        });
    }

    /** Add to "hidden" fields in model */
    public function hideEncryptedAttributes(): void
    {
        if (!$this->secretsHidden) {
            return;
        }
        $this->makeHidden($this->encryptedFields());
    }

    /** Get list of fields */
    protected function encryptedFields(): array
    {
        return static::$encryptedFieldCache[static::class]
            ??= static::getCipherSweetEncryptedRow()->listEncryptedFields();
    }

    /** Gets the field */
    public function reveal(string $field): mixed
    {
        if (!in_array($field, $this->encryptedFields(), true)) {
            return $this->getAttribute($field);
        }

        $this->authorizeReveal($field);

        return $this->attributes[$field] ?? null;
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (
            $value !== null
            && in_array($key, $this->encryptedFields(), true)
        ) {
            return new LazySecureValue($this, $key);
        }

        return $value;
    }

    /** Place for guards */
    protected function authorizeReveal(string $field): void
    {
        // Override in concrete model / policy layer
    }

    /** Clone without secrets */
    public function withoutSecrets(): static
    {
        $clone = clone $this;

        foreach ($this->encryptedFields() as $field) {
            unset($clone->{$field});
        }

        return $clone;
    }

    /** Stop toArray leaks */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach ($this->encryptedFields() as $field) {
            unset($array[$field]);
        }

        return $array;
    }

    /** Stop debug leaks */
    public function __debugInfo(): array
    {
        return [
            'model' => static::class,
            'id' => $this->getKey(),
            'attributes' => '[secure]',
        ];
    }
}

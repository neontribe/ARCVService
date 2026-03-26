<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

abstract class LazySecureModel extends Model
{
    use UsesCipherSweetLazy;

    /**
     * Per-class encrypted field cache.
     */
    protected static array $encryptedFieldCache = [];

    /**
     * Cached decrypted row for this model instance.
     */
    protected ?array $lazyDecryptedRowCache = null;

    protected static function booted(): void
    {
        static::retrieved(static function (self $model) {
            $model->hideEncryptedAttributes();
        });
    }

    public function hideEncryptedAttributes(): static
    {
        $this->makeHidden($this->encryptedFields());
        return $this;
    }

    public function encryptedFields(): array
    {
        return static::$encryptedFieldCache[static::class]
            ??= static::getCipherSweetEncryptedRow()->listEncryptedFields();
    }

    /**
     * Lazy decrypt full encrypted row once, then cache it on the model instance.
     */
    public function decryptEncryptedRowForLazyAccess(): array
    {
        if ($this->lazyDecryptedRowCache !== null) {
            return $this->lazyDecryptedRowCache;
        }

        $row = static::getCipherSweetEncryptedRow()
            ->setPermitEmpty(config('ciphersweet.permit_empty', false));

        $payload = [];

        foreach ($this->encryptedFields() as $field) {
            // Important: use raw/original DB values, not accessors.
            $payload[$field] = $this->getRawOriginal($field);

            // Some hydration paths may not populate "original" as expected.
            // Fall back to raw attributes if needed.
            if (!array_key_exists($field, $this->getOriginal()) && array_key_exists($field, $this->getAttributes())) {
                $payload[$field] = $this->getAttributes()[$field];
            }

            // Ensure every configured encrypted field exists in the payload,
            // even when null, to satisfy CipherSweet row expectations.
            $payload[$field] ??= null;
        }

        return $this->lazyDecryptedRowCache = $row->decryptRow($payload);
    }

    /**
     * If someone accesses $model->email directly and email is encrypted,
     * return a LazySecretValue instead of plaintext/ciphertext.
     */
    public function getAttribute($key): mixed
    {
        if (is_string($key) && $this->isEncryptedField($key)) {
            return $this->secret($key);
        }

        return parent::getAttribute($key);
    }

    public function isEncryptedField(string $field): bool
    {
        return in_array($field, $this->encryptedFields(), true);
    }

    /**
     * Explicit non-magic access to a secret wrapper.
     */
    public function secret(string $field): LazySecureValue
    {
        if (!$this->isEncryptedField($field)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not a configured encrypted field on %s.',
                $field,
                static::class
            ));
        }

        return new LazySecureValue($this, $field);
    }

    /**
     * Remove secrets from array serialization regardless of $hidden changes elsewhere.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach ($this->encryptedFields() as $field) {
            unset($array[$field]);
        }

        return $array;
    }

    /**
     * Safe debug output.
     */
    public function __debugInfo(): array
    {
        return [
            'model' => static::class,
            'id' => $this->getKey(),
            'attributes' => collect(parent::attributesToArray())
                ->except($this->encryptedFields())
                ->all(),
            'hidden_encrypted_fields' => $this->encryptedFields(),
        ];
    }

    /**
     * Override in concrete models, policies, or a shared auth trait.
     */
    public function authorizeReveal(string $field): void
    {
        // no-op by default
    }

    /**
     * Optional helper for safe transport into jobs/events/resources.
     */
    public function withoutSecrets(): static
    {
        $clone = clone $this;
        $clone->flushSecretCache();

        foreach ($clone->encryptedFields() as $field) {
            unset($clone->{$field});
        }

        $clone->makeHidden($clone->encryptedFields());

        return $clone;
    }

    /**
     * Clear cached decrypted values after mutation/refresh.
     */
    public function flushSecretCache(): static
    {
        $this->lazyDecryptedRowCache = null;

        return $this;
    }

    /**
     * Important: whenever attributes are replaced wholesale, clear cache.
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->flushSecretCache();
        return parent::setRawAttributes($attributes, $sync);
    }

    public function refresh()
    {
        $this->flushSecretCache();
        return parent::refresh();
    }
}

<?php

namespace App\Support;

use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Observers\ModelObserver;

/**
 * Modifies the standard trait to avoid decrypting for convenience on model hydration.
 * We do not want the secrets in memory.
 */
trait UsesCipherSweetLazy
{
    use UsesCipherSweet {
        bootUsesCipherSweet as protected baseBoot;
    }

    protected static function bootUsesCipherSweet(): void
    {
        static::$cipherSweetEncryptedRow = null;

        // remove retrieved decrypting hook

        static::saving(
            static function ($m) {
                app(ModelObserver::class)->saving($m);
            }
        );

        static::saved(
            static function ($m) {
                app(ModelObserver::class)->saved($m);
            }
        );

        static::deleting(
            static function ($m) {
                app(ModelObserver::class)->deleting($m);
            }
        );
    }
}

<?php

namespace Tests\Stubs;

use App\Support\LazySecureModel;
use Mockery\MockInterface;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

/**
 * Stub that does NOT override encryptedFields().
 *
 * This lets tests exercise the real caching logic inside
 * LazySecureModel::encryptedFields() without going through the full
 * CipherSweet bootstrap.  Use TestSecureModel for every other scenario.
 */
class CachingTestSecureModel extends LazySecureModel implements CipherSweetEncrypted
{
    protected $table = 'test_secure_models';

    public $timestamps = false;

    protected $guarded = [];

    /** @var EncryptedRow|MockInterface|null */
    private static mixed $stubbedEncryptedRow = null;

    // ── Test-setup helpers ──────────────────────────────────────────────────

    public static function stubEncryptedRow(mixed $row): void
    {
        self::$stubbedEncryptedRow = $row;

        // Bust the inherited per-class cache so a fresh listEncryptedFields()
        // call is triggered on the next encryptedFields() invocation.
        unset(static::$encryptedFieldCache[static::class]);
    }

    public static function resetTestState(): void
    {
        self::$stubbedEncryptedRow = null;
        unset(static::$encryptedFieldCache[static::class]);
    }

    // ── LazySecureModel overrides ───────────────────────────────────────────

    /** @return EncryptedRow|MockInterface */
    public static function getCipherSweetEncryptedRow(): EncryptedRow
    {
        return self::$stubbedEncryptedRow;
    }

    public function authorizeReveal(string $field): void
    {
        // Permissive in tests.
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        // TODO: Implement configureCipherSweet() method.
    }
}

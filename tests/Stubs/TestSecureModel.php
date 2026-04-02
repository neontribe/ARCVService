<?php

namespace Tests\Stubs;

use App\Support\LazySecureModel;
use Illuminate\Auth\Access\AuthorizationException;
use Mockery\MockInterface;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

/**
 * Concrete LazySecureModel for unit tests.
 *
 * Design decisions:
 *   - encryptedFields() returns a fixed list so most tests never need a real
 *     CipherSweet backend—we stay in "field-name land" without decryption.
 *   - A mock EncryptedRow can be injected via stubEncryptedRow() for the
 *     subset of tests that exercise decryption paths.
 *   - authorizeReveal() is a permissive no-op by default.  Call
 *     denyNextReveal() on a model instance to simulate an authorization
 *     failure on the very next reveal() call.
 */
class TestSecureModel extends LazySecureModel implements CipherSweetEncrypted
{
    /** @var EncryptedRow|MockInterface|null */
    private static mixed $stubbedEncryptedRow = null;
    public $timestamps = false;
    protected $table = 'test_secure_models';
    protected $guarded = [];
    private bool $shouldDenyReveal = false;

    // ── Test-setup helpers ──────────────────────────────────────────────────

    public static function stubEncryptedRow(mixed $row): void
    {
        self::$stubbedEncryptedRow = $row;
    }

    /**
     * Wipe all injected state so tests remain isolated from one another.
     */
    public static function resetTestState(): void
    {
        self::$stubbedEncryptedRow = null;

        // Clear the inherited per-class field cache for this stub class.
        unset(static::$encryptedFieldCache[static::class]);
    }

    /** @return EncryptedRow|MockInterface */
    public static function getCipherSweetEncryptedRow(): EncryptedRow
    {
        return self::$stubbedEncryptedRow;
    }

    // ── LazySecureModel overrides ───────────────────────────────────────────

    /**
     * Make the next call to authorizeReveal() on this instance throw.
     */
    public function denyNextReveal(): static
    {
        $this->shouldDenyReveal = true;

        return $this;
    }

    /**
     * Hard-coded to avoid needing a live CipherSweet backend for the
     * majority of tests.
     */
    public function encryptedFields(): array
    {
        return ['secret_field', 'another_secret'];
    }

    public function authorizeReveal(string $field): void
    {
        if ($this->shouldDenyReveal) {
            $this->shouldDenyReveal = false; // auto-reset so the stub stays simple
            throw new AuthorizationException("Reveal denied for field: {$field}");
        }
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        // TODO: Implement configureCipherSweet() method.
    }
}

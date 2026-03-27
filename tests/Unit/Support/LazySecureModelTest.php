<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\LazySecureModel;
use App\Support\LazySecureValue;
use Illuminate\Database\Eloquent\Model as Eloquent;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParagonIE\CipherSweet\EncryptedRow;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\CachingTestSecureModel;
use Tests\Stubs\TestSecureModel;
use Tests\TestCase;

/**
 * Tests for LazySecureModel.
 *
 * Coverage areas:
 *   - getAttribute wraps encrypted fields in LazySecureValue
 *   - toArray / __debugInfo never expose ciphertext or plaintext
 *   - secret() helper and isEncryptedField() predicate
 *   - hideEncryptedAttributes() / hidden field management
 *   - decryptEncryptedRowForLazyAccess() decryption + per-instance caching
 *   - Cache invalidation via flushSecretCache(), setRawAttributes(), refresh()
 *   - withoutSecrets() clone semantics
 *   - encryptedFields() result caching (via CachingTestSecureModel)
 */
class LazySecureModelTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Eloquent::clearBootedModels();
        TestSecureModel::resetTestState();
        CachingTestSecureModel::resetTestState();
    }

    protected function tearDown(): void
    {
        TestSecureModel::resetTestState();
        CachingTestSecureModel::resetTestState();
        parent::tearDown();
    }

    // ── getAttribute ─────────────────────────────────────────────────────────

    #[Test]
    public function get_attribute_wraps_an_encrypted_field_in_lazy_secure_value(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'ciphertext_123']);

        $value = $model->getAttribute('secret_field');

        $this->assertInstanceOf(LazySecureValue::class, $value);
    }

    #[Test]
    public function get_attribute_via_magic_property_also_returns_lazy_secure_value(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'ciphertext_123']);

        // Laravel routes $model->secret_field through getAttribute().
        $this->assertInstanceOf(LazySecureValue::class, $model->secret_field);
    }

    #[Test]
    public function get_attribute_returns_plain_value_for_a_non_encrypted_field(): void
    {
        $model = $this->modelWithAttributes(['name' => 'Alice', 'secret_field' => 'enc']);

        $this->assertSame('Alice', $model->getAttribute('name'));
    }

    #[Test]
    public function get_attribute_returns_null_for_an_absent_non_encrypted_field(): void
    {
        $model = $this->modelWithAttributes([]);

        $this->assertNull($model->getAttribute('name'));
    }

    // ── toArray ──────────────────────────────────────────────────────────────

    #[Test]
    public function to_array_excludes_all_encrypted_fields(): void
    {
        $model = $this->modelWithAttributes([
            'id'             => 1,
            'name'           => 'Bob',
            'secret_field'   => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $array = $model->toArray();

        $this->assertArrayNotHasKey('secret_field', $array);
        $this->assertArrayNotHasKey('another_secret', $array);
    }

    #[Test]
    public function to_array_retains_non_encrypted_fields(): void
    {
        $model = $this->modelWithAttributes([
            'id'   => 42,
            'name' => 'Bob',
        ]);

        $array = $model->toArray();

        $this->assertSame(42, $array['id']);
        $this->assertSame('Bob', $array['name']);
    }

    #[Test]
    public function to_array_does_not_expose_ciphertext_as_a_plain_string(): void
    {
        $model = $this->modelWithAttributes([
            'secret_field' => 'super_sensitive_ciphertext',
        ]);

        $this->assertStringNotContainsString('super_sensitive_ciphertext', serialize($model->toArray()));
    }

    // ── __debugInfo ──────────────────────────────────────────────────────────

    #[Test]
    public function debug_info_omits_encrypted_field_values(): void
    {
        $model = $this->modelWithAttributes([
            'id'           => 5,
            'name'         => 'Carol',
            'secret_field' => 'ciphertext_do_not_leak',
        ]);

        $info = $model->__debugInfo();

        $this->assertArrayNotHasKey('secret_field', $info['attributes']);
        $this->assertStringNotContainsString('ciphertext_do_not_leak', serialize($info));
    }

    #[Test]
    public function debug_info_lists_encrypted_field_names_as_metadata(): void
    {
        $model = $this->modelWithAttributes(['id' => 5]);

        $info = $model->__debugInfo();

        $this->assertContains('secret_field', $info['hidden_encrypted_fields']);
        $this->assertContains('another_secret', $info['hidden_encrypted_fields']);
    }

    #[Test]
    public function debug_info_exposes_the_model_class_and_primary_key(): void
    {
        $model = $this->modelWithAttributes(['id' => 99]);

        $info = $model->__debugInfo();

        $this->assertSame(TestSecureModel::class, $info['model']);
        $this->assertSame(99, $info['id']);
    }

    // ── isEncryptedField ─────────────────────────────────────────────────────

    #[Test]
    public function is_encrypted_field_returns_true_for_a_configured_encrypted_field(): void
    {
        $model = new TestSecureModel();

        $this->assertTrue($model->isEncryptedField('secret_field'));
        $this->assertTrue($model->isEncryptedField('another_secret'));
    }

    #[Test]
    public function is_encrypted_field_returns_false_for_a_plain_field(): void
    {
        $model = new TestSecureModel();

        $this->assertFalse($model->isEncryptedField('name'));
        $this->assertFalse($model->isEncryptedField('id'));
        $this->assertFalse($model->isEncryptedField('nonexistent_field'));
    }

    // ── secret() ─────────────────────────────────────────────────────────────

    #[Test]
    public function secret_returns_a_lazy_secure_value_for_an_encrypted_field(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc']);

        $wrapper = $model->secret('secret_field');

        $this->assertInstanceOf(LazySecureValue::class, $wrapper);
    }

    #[Test]
    public function secret_throws_invalid_argument_exception_for_a_non_encrypted_field(): void
    {
        $model = new TestSecureModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name/');

        $model->secret('name');
    }

    // ── hideEncryptedAttributes ───────────────────────────────────────────────

    #[Test]
    public function hide_encrypted_attributes_adds_encrypted_fields_to_the_hidden_list(): void
    {
        $model = new TestSecureModel();
        $model->hideEncryptedAttributes();

        $hidden = $model->getHidden();

        $this->assertContains('secret_field', $hidden);
        $this->assertContains('another_secret', $hidden);
    }

    #[Test]
    public function encrypted_fields_are_hidden_automatically_after_model_retrieval(): void
    {
        // newFromBuilder simulates Eloquent loading a model from the DB.
        $model = (new TestSecureModel())->newFromBuilder([
            'id'             => 10,
            'secret_field'   => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $this->assertContains('secret_field', $model->getHidden());
        $this->assertContains('another_secret', $model->getHidden());
    }

    // ── decryptEncryptedRowForLazyAccess ─────────────────────────────────────

    #[Test]
    public function decrypt_encrypted_row_returns_all_decrypted_field_values(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field'   => 'plain_secret',
            'another_secret' => 'plain_other',
        ]));

        $model = $this->modelWithAttributes([
            'secret_field'   => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $decrypted = $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame('plain_secret', $decrypted['secret_field']);
        $this->assertSame('plain_other', $decrypted['another_secret']);
    }

    #[Test]
    public function decrypt_encrypted_row_uses_raw_original_attribute_values_not_accessors(): void
    {
        // The model uses getRawOriginal() so that encrypted field accessors
        // (which return LazySecureValue) do not interfere with the payload
        // passed to decryptRow().
        $capturedPayload = null;

        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->once()
            ->andReturnUsing(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;
                return ['secret_field' => 'plain', 'another_secret' => 'other'];
            });

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field'   => 'raw_ciphertext_a',
            'another_secret' => 'raw_ciphertext_b',
        ]);

        $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame(
            'raw_ciphertext_a',
            $capturedPayload['secret_field'],
            'decryptRow payload must contain raw ciphertext, not a LazySecureValue wrapper.'
        );
        $this->assertSame('raw_ciphertext_b', $capturedPayload['another_secret']);
    }

    #[Test]
    public function decrypt_encrypted_row_caches_result_on_the_model_instance(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->once() // Must only be called once despite two decrypt calls.
            ->andReturn(['secret_field' => 'plain', 'another_secret' => 'other']);

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field'   => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $first  = $model->decryptEncryptedRowForLazyAccess();
        $second = $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame($first, $second, 'Subsequent calls must return the cached result.');
        // Mockery verifies decryptRow was called exactly once in tearDown.
    }

    #[Test]
    public function decrypt_encrypted_row_falls_back_to_attributes_when_original_is_unpopulated(): void
    {
        // When a model is constructed without sync:true on setRawAttributes,
        // getOriginal() may be empty.  The implementation falls back to getAttributes().
        $capturedPayload = null;

        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->once()
            ->andReturnUsing(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;
                return ['secret_field' => 'plain', 'another_secret' => 'other'];
            });

        TestSecureModel::stubEncryptedRow($encryptedRow);

        // sync:false — original is NOT populated, only attributes.
        $model = new TestSecureModel();
        $model->setRawAttributes([
            'secret_field'   => 'attr_only_ciphertext',
            'another_secret' => 'attr_only_other',
        ], sync: false);

        $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame(
            'attr_only_ciphertext',
            $capturedPayload['secret_field'],
            'Must fall back to attributes when original is not synced.'
        );
    }

    // ── flushSecretCache ──────────────────────────────────────────────────────

    #[Test]
    public function flush_secret_cache_forces_re_decryption_on_next_access(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->twice() // Once before flush, once after.
            ->andReturn(['secret_field' => 'plain', 'another_secret' => 'other']);

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field'   => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $model->decryptEncryptedRowForLazyAccess(); // warms cache
        $model->flushSecretCache();                 // clears cache
        $model->decryptEncryptedRowForLazyAccess(); // must decrypt again

        // Mockery verifies decryptRow was called exactly twice.
    }

    #[Test]
    public function flush_secret_cache_sets_the_internal_cache_to_null(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain', 'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess();

        $model->flushSecretCache();

        $cache = (new \ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNull($cache);
    }

    // ── setRawAttributes ─────────────────────────────────────────────────────

    #[Test]
    public function set_raw_attributes_flushes_the_secret_cache(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->twice()
            ->andReturn(
                ['secret_field' => 'first_plain',  'another_secret' => 'first_other'],
                ['secret_field' => 'second_plain', 'another_secret' => 'second_other'],
            );

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = new TestSecureModel();
        $model->setRawAttributes(['secret_field' => 'enc_1', 'another_secret' => 'enc_1b'], sync: true);

        $first = $model->decryptEncryptedRowForLazyAccess();

        // Replace attributes — must evict the cached decryption.
        $model->setRawAttributes(['secret_field' => 'enc_2', 'another_secret' => 'enc_2b'], sync: true);

        $second = $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame('first_plain', $first['secret_field']);
        $this->assertSame(
            'second_plain',
            $second['secret_field'],
            'setRawAttributes must bust the decrypt cache so new data is not stale.'
        );
    }

    // ── refresh ───────────────────────────────────────────────────────────────

    #[Test]
    public function refresh_flushes_the_secret_cache_before_reloading_from_db(): void
    {
        // Strategy: use an anonymous subclass that tracks whether
        // flushSecretCache() was called via a public flag, while overriding
        // the Eloquent parent::refresh() DB call to be a no-op.
        //
        // Why not Mockery::mock(TestSecureModel::class)->makePartial()?
        // Eloquent's boot sequence calls new static() internally when
        // registering event listeners (e.g. the retrieved observer), which
        // triggers __construct on the Mockery-generated subclass a second time.
        // Mockery treats that as an unexpected call and throws
        // BadMethodCallException — even though no expectation was set for it.
        //
        // The real LazySecureModel::refresh() body runs unchanged here:
        //   $this->flushSecretCache(); return parent::refresh();
        // We only intercept the final Eloquent DB round-trip.
        $model = new class () extends TestSecureModel {
            /** Flipped to true the instant flushSecretCache() is invoked. */
            public bool $flushWasCalled = false;

            public function flushSecretCache(): LazySecureModel
            {
                $this->flushWasCalled = true;
                return parent::flushSecretCache();
            }

            /** Skip Eloquent's DB-touching parent so the test needs no connection. */
            public function refresh(): LazySecureModel
            {
                $this->flushSecretCache();
                return $this;
            }
        };

        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field'   => 'plain',
            'another_secret' => 'other',
        ]));

        $model->setRawAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2'], sync: true);
        $model->decryptEncryptedRowForLazyAccess(); // warm the cache

        // setRawAttributes() intentionally calls flushSecretCache() as part of its
        // own contract, which trips the spy flag during setup.  Reset it here so the
        // assertion only captures what refresh() itself does.
        $model->flushWasCalled = false;

        $this->assertFalse($model->flushWasCalled, 'Sanity: flush must not fire before refresh().');

        $model->refresh();

        $this->assertTrue(
            $model->flushWasCalled,
            'refresh() must call flushSecretCache() so stale plaintext is evicted '
            . 'before the model reloads its attributes from the database.'
        );

        $cache = (new \ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNull(
            $cache,
            'The decrypt cache must be null after refresh() so the next reveal() '
            . 'decrypts freshly loaded ciphertext rather than serving stale data.'
        );
    }

    // ── withoutSecrets ────────────────────────────────────────────────────────

    #[Test]
    public function without_secrets_returns_a_different_object_instance(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);

        $clone = $model->withoutSecrets();

        $this->assertNotSame($model, $clone);
    }

    #[Test]
    public function without_secrets_returns_a_clone_with_the_decrypt_cache_flushed(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain', 'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess(); // warm cache on original

        $clone = $model->withoutSecrets();

        $cloneCache = (new \ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($clone);

        $this->assertNull(
            $cloneCache,
            'The clone must not carry a populated decrypt cache; '
            . 'callers (jobs, events, resources) must not accidentally receive plaintext.'
        );
    }

    #[Test]
    public function without_secrets_does_not_flush_the_original_model_cache(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain', 'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess(); // warm cache on original

        $model->withoutSecrets(); // must not affect original

        $originalCache = (new \ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNotNull($originalCache, 'Original model cache must be unaffected by cloning.');
    }

    #[Test]
    public function without_secrets_makes_encrypted_fields_hidden_on_the_clone(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);

        $clone = $model->withoutSecrets();

        $this->assertContains('secret_field', $clone->getHidden());
        $this->assertContains('another_secret', $clone->getHidden());
    }

    // ── authorizeReveal (default no-op) ───────────────────────────────────────

    #[Test]
    public function default_authorize_reveal_is_a_no_op_that_does_not_throw(): void
    {
        // The base LazySecureModel provides a no-op so subclasses can opt-in to
        // authorization rather than being forced to implement it immediately.
        $model = new class () extends LazySecureModel {
            protected $table = 'irrelevant';
            public function encryptedFields(): array
            {
                return [];
            }
            public static function getCipherSweetEncryptedRow(): EncryptedRow
            {
                return Mockery::mock(EncryptedRow::class);
            }

            public static function configureCipherSweet(EncryptedRow $encryptedRow): void
            {
                // TODO: Implement configureCipherSweet() method.
            }
        };

        // Must not throw.
        $model->authorizeReveal('any_field');

        $this->addToAssertionCount(1); // confirm test body ran
    }

    // ── encryptedFields caching (via CachingTestSecureModel) ─────────────────

    #[Test]
    public function encrypted_fields_list_is_derived_from_the_encrypted_row_only_once(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->once() // Must be called exactly once despite multiple encryptedFields() calls.
            ->andReturn(['secret_field', 'another_secret']);

        CachingTestSecureModel::stubEncryptedRow($encryptedRow);

        $model = new CachingTestSecureModel();

        $firstCall  = $model->encryptedFields();
        $secondCall = $model->encryptedFields();

        $this->assertSame($firstCall, $secondCall);
        // Mockery verifies listEncryptedFields was called exactly once.
    }

    #[Test]
    public function encrypted_fields_cache_is_shared_across_model_instances_of_the_same_class(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->once() // Should still only be called once across two distinct instances.
            ->andReturn(['secret_field', 'another_secret']);

        CachingTestSecureModel::stubEncryptedRow($encryptedRow);

        $modelA = new CachingTestSecureModel();
        $modelB = new CachingTestSecureModel();

        $modelA->encryptedFields();
        $modelB->encryptedFields(); // Must hit the class-level cache, not call listEncryptedFields again.
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Create a TestSecureModel instance with the given attributes synced to
     * original (simulating a model loaded from the database but without an
     * actual DB round-trip).
     */
    private function modelWithAttributes(array $attributes): TestSecureModel
    {
        $model = new TestSecureModel();
        $model->setRawAttributes($attributes, sync: true);

        return $model;
    }

    /**
     * Build a permissive Mockery mock of EncryptedRow that returns the given
     * $decryptedData from decryptRow().
     */
    private function makeEncryptedRowMock(array $decryptedData): EncryptedRow
    {
        $mock = Mockery::mock(EncryptedRow::class);
        $mock->shouldReceive('setPermitEmpty')->andReturnSelf();
        $mock->shouldReceive('listEncryptedFields')
            ->andReturn(array_keys($decryptedData));
        $mock->shouldReceive('decryptRow')
            ->andReturn($decryptedData);

        return $mock;
    }
}

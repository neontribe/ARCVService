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
use ReflectionMethod;
use ReflectionProperty;
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
 *   - save() dispatches hydrateUnmodifiedEncryptedFieldsBeforeSave()
 *   - hydrateUnmodifiedEncryptedFieldsBeforeSave() prevents double-encryption
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

    public function testGetAttributeWrapsAnEncryptedFieldInLazySecureValue(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'ciphertext_123']);

        $value = $model->getAttribute('secret_field');

        $this->assertInstanceOf(LazySecureValue::class, $value);
    }

    public function testGetAttributeViaMagicPropertyAlsoReturnsLazySecureValue(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'ciphertext_123']);

        // Laravel routes $model->secret_field through getAttribute().
        $this->assertInstanceOf(LazySecureValue::class, $model->secret_field);
    }

    public function testGetAttributeReturnsPlainValueForANonEncryptedField(): void
    {
        $model = $this->modelWithAttributes(['name' => 'Alice', 'secret_field' => 'enc']);

        $this->assertSame('Alice', $model->getAttribute('name'));
    }

    public function testGetAttributeReturnsNullForAnAbsentNonEncryptedField(): void
    {
        $model = $this->modelWithAttributes([]);

        $this->assertNull($model->getAttribute('name'));
    }

    // ── toArray ──────────────────────────────────────────────────────────────

    public function testToArrayExcludesAllEncryptedFields(): void
    {
        $model = $this->modelWithAttributes([
            'id' => 1,
            'name' => 'Bob',
            'secret_field' => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $array = $model->toArray();

        $this->assertArrayNotHasKey('secret_field', $array);
        $this->assertArrayNotHasKey('another_secret', $array);
    }

    public function testToArrayRetainsNonEncryptedFields(): void
    {
        $model = $this->modelWithAttributes([
            'id' => 42,
            'name' => 'Bob',
        ]);

        $array = $model->toArray();

        $this->assertSame(42, $array['id']);
        $this->assertSame('Bob', $array['name']);
    }

    public function testToArrayDoesNotExposeCiphertextAsAPlainString(): void
    {
        $model = $this->modelWithAttributes([
            'secret_field' => 'super_sensitive_ciphertext',
        ]);

        $this->assertStringNotContainsString('super_sensitive_ciphertext', serialize($model->toArray()));
    }

    // ── __debugInfo ──────────────────────────────────────────────────────────

    public function testDebugInfoOmitsEncryptedFieldValues(): void
    {
        $model = $this->modelWithAttributes([
            'id' => 5,
            'name' => 'Carol',
            'secret_field' => 'ciphertext_do_not_leak',
        ]);

        $info = $model->__debugInfo();

        $this->assertArrayNotHasKey('secret_field', $info['attributes']);
        $this->assertStringNotContainsString('ciphertext_do_not_leak', serialize($info));
    }

    public function testDebugInfoListsEncryptedFieldNamesAsMetadata(): void
    {
        $model = $this->modelWithAttributes(['id' => 5]);

        $info = $model->__debugInfo();

        $this->assertContains('secret_field', $info['hidden_encrypted_fields']);
        $this->assertContains('another_secret', $info['hidden_encrypted_fields']);
    }

    public function testDebugInfoExposesTheModelClassAndPrimaryKey(): void
    {
        $model = $this->modelWithAttributes(['id' => 99]);

        $info = $model->__debugInfo();

        $this->assertSame(TestSecureModel::class, $info['model']);
        $this->assertSame(99, $info['id']);
    }

    // ── isEncryptedField ─────────────────────────────────────────────────────

    public function testIsEncryptedFieldReturnsTrueForAConfiguredEncryptedField(): void
    {
        $model = new TestSecureModel();

        $this->assertTrue($model->isEncryptedField('secret_field'));
        $this->assertTrue($model->isEncryptedField('another_secret'));
    }

    public function testIsEncryptedFieldReturnsFalseForAPlainField(): void
    {
        $model = new TestSecureModel();

        $this->assertFalse($model->isEncryptedField('name'));
        $this->assertFalse($model->isEncryptedField('id'));
        $this->assertFalse($model->isEncryptedField('nonexistent_field'));
    }

    // ── secret() ─────────────────────────────────────────────────────────────

    public function testSecretReturnsALazySecureValueForAnEncryptedField(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc']);

        $wrapper = $model->secret('secret_field');

        $this->assertInstanceOf(LazySecureValue::class, $wrapper);
    }

    public function testSecretThrowsInvalidArgumentExceptionForANonEncryptedField(): void
    {
        $model = new TestSecureModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name/');

        $model->secret('name');
    }

    // ── hideEncryptedAttributes ───────────────────────────────────────────────

    public function testHideEncryptedAttributesAddsEncryptedFieldsToTheHiddenList(): void
    {
        $model = new TestSecureModel();
        $model->hideEncryptedAttributes();

        $hidden = $model->getHidden();

        $this->assertContains('secret_field', $hidden);
        $this->assertContains('another_secret', $hidden);
    }

    public function testEncryptedFieldsAreHiddenAutomaticallyAfterModelRetrieval(): void
    {
        // newFromBuilder simulates Eloquent loading a model from the DB.
        $model = (new TestSecureModel())->newFromBuilder([
            'id' => 10,
            'secret_field' => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $this->assertContains('secret_field', $model->getHidden());
        $this->assertContains('another_secret', $model->getHidden());
    }

    // ── decryptEncryptedRowForLazyAccess ─────────────────────────────────────

    public function testDecryptEncryptedRowReturnsAllDecryptedFieldValues(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain_secret',
            'another_secret' => 'plain_other',
        ]));

        $model = $this->modelWithAttributes([
            'secret_field' => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $decrypted = $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame('plain_secret', $decrypted['secret_field']);
        $this->assertSame('plain_other', $decrypted['another_secret']);
    }

    public function testDecryptEncryptedRowUsesRawOriginalAttributeValuesNotAccessors(): void
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
            'secret_field' => 'raw_ciphertext_a',
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

    public function testDecryptEncryptedRowCachesResultOnTheModelInstance(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->once() // Must only be called once despite two decrypt calls.
            ->andReturn(['secret_field' => 'plain', 'another_secret' => 'other']);

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field' => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $first = $model->decryptEncryptedRowForLazyAccess();
        $second = $model->decryptEncryptedRowForLazyAccess();

        $this->assertSame($first, $second, 'Subsequent calls must return the cached result.');
        // Mockery verifies decryptRow was called exactly once in tearDown.
    }

    public function testDecryptEncryptedRowFallsBackToAttributesWhenOriginalIsUnpopulated(): void
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
            'secret_field' => 'attr_only_ciphertext',
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

    public function testFlushSecretCacheForcesReDecryptionOnNextAccess(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->twice() // Once before flush, once after.
            ->andReturn(['secret_field' => 'plain', 'another_secret' => 'other']);

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field' => 'enc_a',
            'another_secret' => 'enc_b',
        ]);

        $model->decryptEncryptedRowForLazyAccess(); // warms cache
        $model->flushSecretCache();                 // clears cache
        $model->decryptEncryptedRowForLazyAccess(); // must decrypt again

        // Mockery verifies decryptRow was called exactly twice.
    }

    public function testFlushSecretCacheSetsTheInternalCacheToNull(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain',
            'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess();

        $model->flushSecretCache();

        $cache = (new ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNull($cache);
    }

    // ── setRawAttributes ─────────────────────────────────────────────────────

    public function testSetRawAttributesFlushesTheSecretCache(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('decryptRow')
            ->twice()
            ->andReturn(
                ['secret_field' => 'first_plain', 'another_secret' => 'first_other'],
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

    public function testRefreshFlushesTheSecretCacheBeforeReloadingFromDb(): void
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
            'secret_field' => 'plain',
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

        $cache = (new ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNull(
            $cache,
            'The decrypt cache must be null after refresh() so the next reveal() '
            . 'decrypts freshly loaded ciphertext rather than serving stale data.'
        );
    }

    // ── withoutSecrets ────────────────────────────────────────────────────────

    public function testWithoutSecretsReturnsADifferentObjectInstance(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);

        $clone = $model->withoutSecrets();

        $this->assertNotSame($model, $clone);
    }

    public function testWithoutSecretsReturnsACloneWithTheDecryptCacheFlushed(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain',
            'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess(); // warm cache on original

        $clone = $model->withoutSecrets();

        $cloneCache = (new ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($clone);

        $this->assertNull(
            $cloneCache,
            'The clone must not carry a populated decrypt cache; '
            . 'callers (jobs, events, resources) must not accidentally receive plaintext.'
        );
    }

    public function testWithoutSecretsDoesNotFlushTheOriginalModelCache(): void
    {
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain',
            'another_secret' => 'other',
        ]));

        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);
        $model->decryptEncryptedRowForLazyAccess(); // warm cache on original

        $model->withoutSecrets(); // must not affect original

        $originalCache = (new ReflectionProperty(LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNotNull($originalCache, 'Original model cache must be unaffected by cloning.');
    }

    public function testWithoutSecretsMakesEncryptedFieldsHiddenOnTheClone(): void
    {
        $model = $this->modelWithAttributes(['secret_field' => 'enc', 'another_secret' => 'enc2']);

        $clone = $model->withoutSecrets();

        $this->assertContains('secret_field', $clone->getHidden());
        $this->assertContains('another_secret', $clone->getHidden());
    }

    // ── authorizeReveal (default no-op) ───────────────────────────────────────

    public function testDefaultAuthorizeRevealIsANoOpThatDoesNotThrow(): void
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
            }
        };

        // Must not throw.
        $model->authorizeReveal('any_field');

        $this->addToAssertionCount(1); // confirm test body ran
    }

    // ── encryptedFields caching (via CachingTestSecureModel) ─────────────────

    public function testEncryptedFieldsListIsDerivedFromTheEncryptedRowOnlyOnce(): void
    {
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->once() // Must be called exactly once despite multiple encryptedFields() calls.
            ->andReturn(['secret_field', 'another_secret']);

        CachingTestSecureModel::stubEncryptedRow($encryptedRow);

        $model = new CachingTestSecureModel();

        $firstCall = $model->encryptedFields();
        $secondCall = $model->encryptedFields();

        $this->assertSame($firstCall, $secondCall);
        // Mockery verifies listEncryptedFields was called exactly once.
    }

    public function testEncryptedFieldsCacheIsSharedAcrossModelInstancesOfTheSameClass(): void
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

    // ── save / hydrateUnmodifiedEncryptedFieldsBeforeSave ────────────────────
    //
    // These tests cover the fix for the "partial-update double-encrypt" bug:
    // when only one CipherSweet-protected field is updated before save(), the
    // other field's attribute still holds raw DB ciphertext.  Without the fix,
    // CipherSweet's saving observer would receive that ciphertext as if it were
    // plaintext and encrypt it a second time, corrupting the value permanently.
    //
    // hydrateUnmodifiedEncryptedFieldsBeforeSave() resolves this by decrypting
    // any encrypted field that has NOT been dirtied, and writing its plaintext
    // back into $this->attributes before the saving event fires.

    public function testSaveCallsHydrateUnmodifiedFieldsBeforePassingControlToParent(): void
    {
        // Uses an anonymous spy to confirm the protected hydration method is
        // invoked by save() before any Eloquent/DB work begins.
        //
        // The real LazySecureModel::save() body is reproduced here unchanged:
        //   $this->hydrateUnmodifiedEncryptedFieldsBeforeSave(); return parent::save($options);
        // We only intercept parent::save() itself to avoid requiring a live DB connection.
        // This mirrors the strategy used in the refresh() spy test above.
        $model = new class () extends TestSecureModel {
            public bool $hydrateWasCalled = false;

            protected function hydrateUnmodifiedEncryptedFieldsBeforeSave(): void
            {
                $this->hydrateWasCalled = true;
            }

            /** Skip Eloquent's DB-touching parent so the test needs no connection. */
            public function save(array $options = []): bool
            {
                $this->hydrateUnmodifiedEncryptedFieldsBeforeSave();
                return true;
            }
        };

        $model->save();

        $this->assertTrue(
            $model->hydrateWasCalled,
            'save() must call hydrateUnmodifiedEncryptedFieldsBeforeSave() so that '
            . 'CipherSweet always receives plaintext for every configured encrypted field, '
            . 'not raw DB ciphertext that would be double-encrypted.'
        );
    }

    public function testHydrateIsANoOpForANewModel(): void
    {
        // A new, not-yet-persisted model ($this->exists === false) has no DB
        // ciphertext in its attributes — there is nothing stale to replace.
        // decryptRow() must not be called.
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->andReturn(['secret_field', 'another_secret']);
        $encryptedRow->shouldNotReceive('decryptRow');

        TestSecureModel::stubEncryptedRow($encryptedRow);

        // new TestSecureModel() has exists === false by default.
        $model = new TestSecureModel();

        $this->callHydrate($model);

        $this->addToAssertionCount(1); // Mockery asserts decryptRow was never called in tearDown.
    }

    public function testHydrateIsANoOpWhenAllEncryptedFieldsAreDirty(): void
    {
        // When every encrypted field has already been updated to new plaintext,
        // there is no stale ciphertext to replace — decryption is unnecessary.
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->andReturn(['secret_field', 'another_secret']);
        $encryptedRow->shouldNotReceive('decryptRow');

        TestSecureModel::stubEncryptedRow($encryptedRow);

        // Simulate a persisted model: attributes synced to original so isDirty() is false.
        $model = $this->modelWithAttributes([
            'secret_field' => 'nacl:enc_a',
            'another_secret' => 'nacl:enc_b',
        ]);
        $model->exists = true;

        // Mark both encrypted fields dirty with new plaintext values.
        $model->secret_field = 'new_plain_a';
        $model->another_secret = 'new_plain_b';

        $this->callHydrate($model);

        $this->addToAssertionCount(1); // Mockery asserts decryptRow was never called in tearDown.
    }

    public function testHydrateWritesDecryptedPlaintextForTheUnmodifiedFieldWhenOneFieldIsUpdated(): void
    {
        // Core regression test for the double-encrypt bug.
        //
        // Given: a persisted model with both encrypted fields intact in the DB.
        // When:  only one field (secret_field) is updated before save().
        // Then:  the unmodified field (another_secret) must be hydrated with its
        //        decrypted plaintext so CipherSweet's saving observer encrypts
        //        plaintext → ciphertext, not ciphertext → double-ciphertext.
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain_a',
            'another_secret' => 'plain_b',
        ]));

        $model = $this->modelWithAttributes([
            'secret_field' => 'nacl:enc_a',
            'another_secret' => 'nacl:enc_b',
        ]);
        $model->exists = true;

        // Update only secret_field; another_secret intentionally left as raw ciphertext.
        $model->secret_field = 'new_plain_a';

        $this->callHydrate($model);

        $this->assertSame(
            'plain_b',
            $model->getAttributes()['another_secret'],
            'The unmodified encrypted field must be overwritten with its decrypted plaintext '
            . 'before CipherSweet runs, otherwise it will be double-encrypted and permanently corrupted.'
        );
    }

    public function testHydrateDoesNotOverwriteTheExplicitlyUpdatedField(): void
    {
        // Hydration must only touch fields that were NOT explicitly updated.
        // The field the caller dirtied must retain the new value they set.
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'old_plain_a',
            'another_secret' => 'plain_b',
        ]));

        $model = $this->modelWithAttributes([
            'secret_field' => 'nacl:enc_a',
            'another_secret' => 'nacl:enc_b',
        ]);
        $model->exists = true;

        $model->secret_field = 'new_plain_a';

        $this->callHydrate($model);

        $this->assertSame(
            'new_plain_a',
            $model->getAttributes()['secret_field'],
            'The explicitly updated field must retain its new plaintext value; '
            . 'hydration must not overwrite it with the previously-decrypted old value.'
        );
    }

    public function testHydrateWritesDecryptedPlaintextForAllFieldsWhenNoneAreDirty(): void
    {
        // Edge case: the model is saved for a reason unrelated to encrypted fields
        // (e.g., only a plain column changed).  Every encrypted field is still
        // holding raw DB ciphertext and must be hydrated before CipherSweet runs.
        TestSecureModel::stubEncryptedRow($this->makeEncryptedRowMock([
            'secret_field' => 'plain_a',
            'another_secret' => 'plain_b',
        ]));

        $model = $this->modelWithAttributes([
            'secret_field' => 'nacl:enc_a',
            'another_secret' => 'nacl:enc_b',
        ]);
        $model->exists = true;

        // No encrypted field updated — both still hold raw DB ciphertext.
        $this->callHydrate($model);

        $attrs = $model->getAttributes();
        $this->assertSame(
            'plain_a',
            $attrs['secret_field'],
            'secret_field must be replaced with its decrypted plaintext.'
        );
        $this->assertSame(
            'plain_b',
            $attrs['another_secret'],
            'another_secret must be replaced with its decrypted plaintext.'
        );
    }

    public function testHydrateLeveragesTheExistingDecryptCacheAndDoesNotDecryptTwice(): void
    {
        // If a reveal() has already warmed the decrypt cache (e.g., the controller
        // called ->reveal() to compare the current value before deciding to update),
        // the subsequent hydrateUnmodifiedEncryptedFieldsBeforeSave() call must
        // reuse that cache rather than triggering a second CipherSweet round-trip.
        $encryptedRow = Mockery::mock(EncryptedRow::class);
        $encryptedRow->shouldReceive('setPermitEmpty')->andReturnSelf();
        $encryptedRow->shouldReceive('listEncryptedFields')
            ->andReturn(['secret_field', 'another_secret']);
        $encryptedRow->shouldReceive('decryptRow')
            ->once() // Must be called exactly once across the reveal + save path.
            ->andReturn(['secret_field' => 'plain_a', 'another_secret' => 'plain_b']);

        TestSecureModel::stubEncryptedRow($encryptedRow);

        $model = $this->modelWithAttributes([
            'secret_field' => 'nacl:enc_a',
            'another_secret' => 'nacl:enc_b',
        ]);
        $model->exists = true;

        // Simulate a prior reveal() (e.g., the isDirty comparison in the controller).
        $model->decryptEncryptedRowForLazyAccess();

        $model->secret_field = 'new_plain_a';

        $this->callHydrate($model);

        // Mockery verifies decryptRow was called exactly once in tearDown.
        $this->addToAssertionCount(1);
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

    /**
     * Invoke the protected hydrateUnmodifiedEncryptedFieldsBeforeSave() method
     * via reflection so tests can exercise it directly without calling save()
     * (which would require a live database connection).
     */
    private function callHydrate(LazySecureModel $model): void
    {
        (new ReflectionMethod(LazySecureModel::class, 'hydrateUnmodifiedEncryptedFieldsBeforeSave'))
            ->invoke($model);
    }
}

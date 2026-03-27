<?php

namespace Tests\Unit\Support;

use App\Support\LazySecureValue;
use Illuminate\Auth\Access\AuthorizationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParagonIE\CipherSweet\EncryptedRow;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\TestSecureModel;
use Tests\TestCase;

/**
 * Tests for LazySecureValue.
 *
 * LazySecureValue is a thin wrapper around a (model, field) pair that:
 *   - Always serialises to a safe "[secret]" placeholder.
 *   - Only decrypts when reveal() is called explicitly.
 *   - Delegates authorization to the model before decrypting.
 */
class LazySecureValueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private TestSecureModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        // A model instance with raw (encrypted) bytes set as if freshly loaded
        // from the database.  No actual DB interaction required.
        $this->model = new TestSecureModel();
        $this->model->setRawAttributes([
            'secret_field'   => 'ciphertext_abc',
            'another_secret' => 'ciphertext_xyz',
        ], sync: true);
    }

    protected function tearDown(): void
    {
        TestSecureModel::resetTestState();
        parent::tearDown();
    }

    // ── __toString ───────────────────────────────────────────────────────────

    #[Test]
    public function string_cast_returns_the_safe_placeholder(): void
    {
        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertSame('[secret]', (string) $value);
    }

    // ── jsonSerialize ────────────────────────────────────────────────────────

    #[Test]
    public function json_encoding_returns_the_safe_placeholder(): void
    {
        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertSame('"[secret]"', json_encode($value));
    }

    #[Test]
    public function json_encoding_inside_an_array_returns_the_placeholder_string(): void
    {
        $value = new LazySecureValue($this->model, 'secret_field');

        $encoded = json_decode(json_encode(['key' => $value]), associative: true);

        $this->assertSame('[secret]', $encoded['key']);
    }

    // ── __debugInfo ──────────────────────────────────────────────────────────

    #[Test]
    public function debug_info_exposes_only_safe_metadata(): void
    {
        $value = new LazySecureValue($this->model, 'secret_field');

        $info = $value->__debugInfo();

        $this->assertSame('[hidden]', $info['secret'], 'plaintext must never appear in debug output');
        $this->assertSame('secret_field', $info['field'], 'field name is safe to expose');
        $this->assertSame(TestSecureModel::class, $info['model'], 'model class is safe to expose');
    }

    #[Test]
    public function debug_info_does_not_contain_the_raw_ciphertext_or_plaintext(): void
    {
        $value = new LazySecureValue($this->model, 'secret_field');

        $raw = print_r($value->__debugInfo(), return: true);

        $this->assertStringNotContainsString('ciphertext_abc', $raw);
    }

    // ── reveal ───────────────────────────────────────────────────────────────

    #[Test]
    public function reveal_returns_the_decrypted_value_for_the_wrapped_field(): void
    {
        TestSecureModel::stubEncryptedRow($this->mockEncryptedRow([
            'secret_field'   => 'decrypted_secret',
            'another_secret' => 'decrypted_other',
        ]));

        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertSame('decrypted_secret', $value->reveal());
    }

    #[Test]
    public function reveal_returns_null_when_the_field_is_absent_from_the_decrypted_row(): void
    {
        // Simulate a row that decrypts without the requested field.
        TestSecureModel::stubEncryptedRow($this->mockEncryptedRow([
            'another_secret' => 'decrypted_other',
            // 'secret_field' intentionally absent
        ]));

        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertNull($value->reveal());
    }

    #[Test]
    public function reveal_calls_authorize_reveal_on_the_model_before_decrypting(): void
    {
        // Partial mock so we can spy on authorizeReveal without changing decryption.
        $spyModel = Mockery::mock(TestSecureModel::class)->makePartial();
        $spyModel->setRawAttributes([
            'secret_field'   => 'ciphertext_abc',
            'another_secret' => 'ciphertext_xyz',
        ], sync: true);

        $spyModel->shouldReceive('authorizeReveal')
            ->once()
            ->with('secret_field');

        $spyModel->shouldReceive('decryptEncryptedRowForLazyAccess')
            ->once()
            ->andReturn(['secret_field' => 'decrypted_secret']);

        $value = new LazySecureValue($spyModel, 'secret_field');
        $value->reveal();
        // Mockery verifies expectations on tearDown.
    }

    #[Test]
    public function reveal_propagates_authorization_exceptions_from_the_model(): void
    {
        $this->model->denyNextReveal();

        // Stub a row so we don't get "null EncryptedRow" before the auth check.
        TestSecureModel::stubEncryptedRow($this->mockEncryptedRow([
            'secret_field' => 'decrypted',
        ]));

        $value = new LazySecureValue($this->model, 'secret_field');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessageMatches('/secret_field/');

        $value->reveal();
    }

    #[Test]
    public function reveal_never_bypasses_authorization_to_reach_the_decrypted_value(): void
    {
        $this->model->denyNextReveal();

        $encryptedRowMock = $this->mockEncryptedRow(['secret_field' => 'should_not_appear']);
        // decryptRow must NOT be called when authorization fails.
        $encryptedRowMock->shouldReceive('decryptRow')->never();
        TestSecureModel::stubEncryptedRow($encryptedRowMock);

        $value = new LazySecureValue($this->model, 'secret_field');

        try {
            $value->reveal();
        } catch (AuthorizationException) {
            // expected
        }
    }

    // ── isNull ───────────────────────────────────────────────────────────────

    #[Test]
    public function is_null_returns_true_when_the_decrypted_field_value_is_null(): void
    {
        TestSecureModel::stubEncryptedRow($this->mockEncryptedRow([
            'secret_field'   => null,
            'another_secret' => 'decrypted_other',
        ]));

        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertTrue($value->isNull());
    }

    #[Test]
    public function is_null_returns_false_when_the_decrypted_field_has_a_value(): void
    {
        TestSecureModel::stubEncryptedRow($this->mockEncryptedRow([
            'secret_field'   => 'non_null_value',
            'another_secret' => 'decrypted_other',
        ]));

        $value = new LazySecureValue($this->model, 'secret_field');

        $this->assertFalse($value->isNull());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a Mockery mock of EncryptedRow that returns $decryptedData from
     * decryptRow() and is otherwise permissive.
     */
    private function mockEncryptedRow(array $decryptedData): EncryptedRow
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

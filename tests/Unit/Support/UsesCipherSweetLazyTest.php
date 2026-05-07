<?php

namespace Tests\Unit\Support;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LaravelCipherSweet\Observers\ModelObserver;
use Tests\Stubs\TestSecureModel;
use Tests\TestCase;

/**
 * Tests for the UsesCipherSweetLazy trait.
 *
 * The trait's sole responsibility is to modify the boot sequence inherited
 * from UsesCipherSweet so that:
 *
 *  1. The `retrieved` event no longer decrypts attributes automatically
 *     (secrets stay as ciphertext in memory until explicitly revealed).
 *  2. The `saving`, `saved`, and `deleting` events still delegate to
 *     ModelObserver so CipherSweet can keep encrypted indexes up to date.
 */
class UsesCipherSweetLazyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        // Force a fresh boot for every test so event listeners are re-registered
        // against the current test's container / mock bindings.
        Eloquent::clearBootedModels();
        TestSecureModel::resetTestState();
    }

    protected function tearDown(): void
    {
        TestSecureModel::resetTestState();
        parent::tearDown();
    }

    // ── retrieved: no auto-decrypt ───────────────────────────────────────────

    #[Test]
    public function retrieved_event_does_not_populate_the_decrypted_row_cache(): void
    {
        // newFromBuilder() simulates Eloquent hydrating a model from query results
        // and fires the `retrieved` event.  The base UsesCipherSweet trait would
        // decrypt here; UsesCipherSweetLazy intentionally skips that step.
        $model = (new TestSecureModel())->newFromBuilder([
            'id'             => 1,
            'secret_field'   => 'ciphertext_abc',
            'another_secret' => 'ciphertext_xyz',
        ]);

        // Access the protected $lazyDecryptedRowCache via reflection.
        $cache = (new \ReflectionProperty(\App\Support\LazySecureModel::class, 'lazyDecryptedRowCache'))
            ->getValue($model);

        $this->assertNull($cache, 'lazyDecryptedRowCache must remain null after retrieval; '
            . 'secrets should not be decrypted into memory automatically.');
    }

    #[Test]
    public function raw_attributes_retain_ciphertext_after_model_is_hydrated(): void
    {
        $model = (new TestSecureModel())->newFromBuilder([
            'id'           => 2,
            'secret_field' => 'raw_ciphertext_value',
        ]);

        // getRawOriginal returns the value as stored; it must still be ciphertext.
        $this->assertSame(
            'raw_ciphertext_value',
            $model->getRawOriginal('secret_field'),
            'Ciphertext must not be replaced by plaintext after retrieval.'
        );
    }

    // ── saving ───────────────────────────────────────────────────────────────

    #[Test]
    public function saving_event_invokes_model_observer_saving(): void
    {
        $model = new TestSecureModel();

        $observer = Mockery::mock(ModelObserver::class);
        $observer->shouldReceive('saving')->once()->with($model);
        $this->app->instance(ModelObserver::class, $observer);

        $this->fireEloquentEvent('saving', $model);
    }

    // ── saved ────────────────────────────────────────────────────────────────

    #[Test]
    public function saved_event_invokes_model_observer_saved(): void
    {
        $model = new TestSecureModel();

        $observer = Mockery::mock(ModelObserver::class);
        $observer->shouldReceive('saved')->once()->with($model);
        $this->app->instance(ModelObserver::class, $observer);

        $this->fireEloquentEvent('saved', $model);
    }

    // ── deleting ─────────────────────────────────────────────────────────────

    #[Test]
    public function deleting_event_invokes_model_observer_deleting(): void
    {
        $model = new TestSecureModel();

        $observer = Mockery::mock(ModelObserver::class);
        $observer->shouldReceive('deleting')->once()->with($model);
        $this->app->instance(ModelObserver::class, $observer);

        $this->fireEloquentEvent('deleting', $model);
    }

    // ── listener presence (fast sanity checks) ───────────────────────────────

    #[Test]
    public function saving_listener_is_registered_on_the_model(): void
    {
        // Instantiating the model triggers its boot; the trait must register
        // a saving listener.
        new TestSecureModel();

        $listeners = TestSecureModel::getEventDispatcher()
            ->getListeners('eloquent.saving: ' . TestSecureModel::class);

        $this->assertNotEmpty($listeners, 'A saving listener must be registered.');
    }

    #[Test]
    public function saved_listener_is_registered_on_the_model(): void
    {
        new TestSecureModel();

        $listeners = TestSecureModel::getEventDispatcher()
            ->getListeners('eloquent.saved: ' . TestSecureModel::class);

        $this->assertNotEmpty($listeners, 'A saved listener must be registered.');
    }

    #[Test]
    public function deleting_listener_is_registered_on_the_model(): void
    {
        new TestSecureModel();

        $listeners = TestSecureModel::getEventDispatcher()
            ->getListeners('eloquent.deleting: ' . TestSecureModel::class);

        $this->assertNotEmpty($listeners, 'A deleting listener must be registered.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Dispatch an Eloquent model event directly through the shared dispatcher
     * without touching the database.
     *
     * Eloquent registers listeners under "eloquent.{event}: {ClassName}".
     * Dispatching here triggers the static closures registered during boot,
     * which in turn call app(ModelObserver::class)->{event}($model).
     */
    private function fireEloquentEvent(string $event, Eloquent $model): void
    {
        $key = sprintf('eloquent.%s: %s', $event, $model::class);

        TestSecureModel::getEventDispatcher()->dispatch($key, $model);
    }
}

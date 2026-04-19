<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\StoreTestCase;

class StoreAppendBundleRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    protected Centre $centre;
    protected CentreUser $centreUser;
    protected Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        $this->centreUser = factory(CentreUser::class)->create([
            'name' => 'test user',
            'email' => 'testuser@example.com',
            'password' => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        // Auth context is required by the voucher state-machine transition logger.
        Auth::login($this->centreUser);

        foreach (['TST09999', 'TST10000', 'TST10001'] as $code) {
            $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
            $voucher->applyTransition('dispatch');
        }

        Auth::logout();
    }

    // -------------------------------------------------------------------------
    // Data provider
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{array<string,string>, string, string}>
     * Config placeholders: config() is unavailable in static providers (they are constructed
     * before the Laravel application boots). Two placeholders are used instead and resolved
     * inside the test method once the container is live:
     *   :max:          → config('arc.bundle_max_voucher_append')
     *   :max_plus_one: → config('arc.bundle_max_voucher_append') + 1
     */
    public static function validationCases(): array
    {
        return [
            // --- start field (voucher-quantity absent branch) ---
            'start: absent with no data submitted' => [
                [],
                'start',
                'The start field is required when voucher-quantity is not present.',
            ],
            'start: absent when only end supplied' => [
                ['end' => 'tst10001'],
                'start',
                'The start field is required when voucher-quantity is not present.',
            ],
            'start: empty string stripped by prepareForValidation' => [
                ['start' => '', 'end' => 'tst10001'],
                'start',
                'The start field is required when voucher-quantity is not present.',
            ],
            'start: value not found in vouchers table' => [
                ['start' => 'invalidVoucher', 'end' => 'tst10001'],
                'start',
                'The selected start is invalid.',
            ],

            // --- end field ---
            'end: value not found in vouchers table' => [
                ['start' => 'tst09999', 'end' => 'invalidCode'],
                'end',
                'The selected end is invalid.',
            ],
            'end: different sponsor prefix to start' => [
                ['start' => 'tst09999', 'end' => 'txt10000'],
                'end',
                'The end field must be the same sponsor as the start field.',
            ],
            'end: lower sequence number than start' => [
                ['start' => 'tst10001', 'end' => 'tst09999'],
                'end',
                'The end field must be greater than the start field.',
            ],

            // --- voucher-quantity field (early-return branch) ---
            'voucher-quantity: zero is below the allowed minimum of 1' => [
                ['voucher-quantity' => '0'],
                'voucher-quantity',
                'The voucher-quantity must be between 1 and :max:.',
            ],
            'voucher-quantity: negative value is below the allowed minimum of 1' => [
                ['voucher-quantity' => '-1'],
                'voucher-quantity',
                'The voucher-quantity must be between 1 and :max:.',
            ],
            'voucher-quantity: exceeds the configured maximum' => [
                ['voucher-quantity' => ':max_plus_one:'],
                'voucher-quantity',
                'The voucher-quantity must be between 1 and :max:.',
            ],
            'voucher-quantity: required when start also absent' => [
                [],
                'voucher-quantity',
                'The voucher-quantity field is required when start is not present.',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[DataProvider('validationCases')]
    public function testInvalidInputIsRejected(
        array $data,
        string $field,
        string $expectedMessage,
    ): void {
        $max = (string)config('arc.bundle_max_voucher_append');

        // Resolve placeholders that cannot be evaluated inside a static data provider.
        $data = array_map(static function (string $v) use ($max) {
            return str_replace(':max_plus_one:', (string)((int)$max + 1), $v);
        }, $data);
        $expectedMessage = str_replace(':max:', $max, $expectedMessage);

        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->post($postRoute, $data);

        $errors = session('errors')->get($field);

        $this->assertNotEmpty($errors, "Expected validation errors for field '{$field}'");
        $this->assertContains($expectedMessage, $errors);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }
}

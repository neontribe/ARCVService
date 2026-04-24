<?php

namespace Tests\Unit\FormRequests;

use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\StoreTestCase;

class StoreTransitionBundleRequestTest extends StoreTestCase
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

        $this->carer = Carer::first();
    }

    // -------------------------------------------------------------------------
    // Data provider
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{array<string,string>, string, string}>
     */
    public static function validationCases(): array
    {
        return [
            // --- required fields ---
            'trader_id missing' => [
                ['collected_at' => '1', 'collected_on' => '2018-07-21', 'collected_by' => '1'],
                'trader_id',
                'The trader id field is required.',
            ],
            'collected_at missing' => [
                ['trader_id' => '1', 'collected_on' => '2018-07-21', 'collected_by' => '1'],
                'collected_at',
                'The collected at field is required.',
            ],
            'collected_on missing' => [
                ['trader_id' => '1', 'collected_at' => '1', 'collected_by' => '1'],
                'collected_on',
                'The collected on field is required.',
            ],
            'collected_by missing' => [
                ['trader_id' => '1', 'collected_at' => '1', 'collected_on' => '2018-07-21'],
                'collected_by',
                'The collected by field is required.',
            ],

            // --- format/existence ---
            'collected_on wrong date format' => [
                ['trader_id' => '1', 'collected_at' => '1', 'collected_on' => 'invalid', 'collected_by' => '1'],
                'collected_on',
                'The collected on does not match the format Y-m-d.',
            ],
            'collected_at centre does not exist' => [
                ['trader_id' => '1', 'collected_at' => '9999', 'collected_on' => '2018-07-21', 'collected_by' => '1'],
                'collected_at',
                'The selected collected at is invalid.',
            ],
            'collected_by carer does not exist' => [
                ['trader_id' => '1', 'collected_at' => '1', 'collected_on' => '2018-07-21', 'collected_by' => '9999'],
                'collected_by',
                'The selected collected by is invalid.',
            ],
            'trader_id trader does not exist' => [
                ['trader_id' => '9999', 'collected_at' => '1', 'collected_on' => '2018-07-21', 'collected_by' => '1'],
                'trader_id',
                'The selected trader id is invalid.',
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
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->put($postRoute, $data);

        $errors = session('errors')->get($field);

        $this->assertNotEmpty($errors, "Expected validation errors for field '{$field}'");
        $this->assertContains($expectedMessage, $errors);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    public function testValidInputIsAccepted(): void
    {
        $trader = factory(Trader::class)->create();

        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->put($postRoute, [
                'trader_id' => (string)$trader->id,
                'collected_at' => (string)$this->centre->id,
                'collected_on' => '2018-07-21',
                'collected_by' => (string)$this->carer->id,
            ]);
        $this->assertSessionMissing('errors');
    }
}

<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminNewUpdateMarketRequest;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminNewUpdateMarketRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new AdminNewUpdateMarketRequest())->rules();
        factory(Sponsor::class)->create();
    }

    private function validate(array $data): bool
    {
        return Validator::make($data, $this->rules)->passes();
    }

    /**
     * @dataProvider validationCases
     */
    public function testItValidatesMarketRequests(bool $expected, array $data): void
    {
        $this->assertEquals($expected, $this->validate($data));
    }

    public static function validationCases(): array
    {
        return [
            'Valid request' => [true, [
                'name' => 'Test Market',
                'sponsor' => 1,
                'payment_message' => 'a message',
            ]],
            'Missing name' => [false, [
                'sponsor' => 1,
                'payment_message' => 'a message',
            ]],
            'Name is not a string' => [false, [
                'name' => 1,
                'sponsor' => 1,
                'payment_message' => 'a message',
            ]],
            'Missing sponsor' => [false, [
                'name' => 'Test Market',
                'payment_message' => 'a message',
            ]],
            'Sponsor is not an integer' => [false, [
                'name' => 'Test Market',
                'sponsor' => 'f',
                'payment_message' => 'a message',
            ]],
            'Invalid sponsor' => [false, [
                'name' => 'Test Market',
                'sponsor' => 999,
                'payment_message' => 'a message',
            ]],
            'Missing payment message' => [false, [
                'name' => 'Test Market',
                'sponsor' => 1,
            ]],
            'Payment message is not a string' => [false, [
                'name' => 'Test Market',
                'sponsor' => 1,
                'payment_message' => 1,
            ]],
            'Payment message is less than one character' => [false, [
                'name' => 'Test Market',
                'sponsor' => 1,
                'payment_message' => '',
            ]],
            'Payment message exceeds 160 characters' => [false, [
                'name' => 'Test Market',
                'sponsor' => 1,
                'payment_message' => str_repeat('a', 161),
            ]],
            'Payment message within 160 characters' => [true, [
                'name' => 'Test Market',
                'sponsor' => 1,
                'payment_message' => str_repeat('a', 160),
            ]],
        ];
    }
}

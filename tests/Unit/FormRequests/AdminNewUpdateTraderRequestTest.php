<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminNewUpdateTraderRequest;
use App\Market;
use App\Sponsor;
use App\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminNewUpdateTraderRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $sponsor = factory(Sponsor::class)->create();
        $market = factory(Market::class)->create(['sponsor_id' => $sponsor->id]);
        factory(Trader::class)->create(['market_id' => $market->id]);

        $this->rules = (new AdminNewUpdateTraderRequest())->rules();
    }

    private function validate(array $data): bool
    {
        return Validator::make($data, $this->rules)->passes();
    }

    /**
     * @dataProvider validationCases
     */
    public function testItValidatesTraderRequests(bool $expected, array $data): void
    {
        $this->assertEquals($expected, $this->validate($data));
    }

    public static function validationCases(): array
    {
        return [
            'Valid minimal data' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
            ]],
            'Valid optional data (boolean int)' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'disabled' => 1,
            ]],
            'Valid optional data (null)' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'disabled' => null,
            ]],
            'Valid optional data (boolean)' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'disabled' => true,
            ]],
            'Valid optional data (text boolean)' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'disabled' => 'on',
            ]],
            'Missing name' => [false, [
                'market' => 1,
            ]],
            'Name not a string' => [false, [
                'name' => 1,
                'market' => 1,
            ]],
            'Name exceeds 160 characters' => [false, [
                'name' => str_repeat('a', 161),
                'market' => 1,
            ]],
            'Valid name within 160 characters' => [true, [
                'name' => str_repeat('a', 160),
                'market' => 1,
            ]],
            'Missing market' => [false, [
                'name' => 'Test Trader',
            ]],
            'Market not an integer' => [false, [
                'name' => 'Test Trader',
                'market' => 'f',
            ]],
            'Invalid market' => [false, [
                'name' => 'Test Trader',
                'market' => 999,
            ]],
            'Valid minimal user data' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                ],
            ]],
            'Users not an array' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => 'thing',
            ]],
            'Users is an empty array' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [],
            ]],
            'User name not a string' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 1, 'email' => 'valid@example.com'],
                ],
            ]],
            'Valid user name within 160 characters' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => str_repeat('a', 160), 'email' => 'valid@example.com'],
                ],
            ]],
            'User name exceeds 160 characters' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => str_repeat('a', 161), 'email' => 'valid@example.com'],
                ],
            ]],
            'Missing user name' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['email' => 'valid@example.com'],
                ],
            ]],
            'Missing user email' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 'Test Name'],
                ],
            ]],
            'Invalid user email' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 'Test Name', 'email' => 'notAValidMail.com'],
                ],
            ]],
            'Valid multiple users' => [true, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                    11 => ['name' => 'Test Name 2', 'email' => 'valid2@example.com'],
                ],
            ]],
            'Multiple users with missing data' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['email' => 'valid@example.com'],
                    11 => ['name' => 'Test Name 2'],
                ],
            ]],
            'Duplicated emails among users' => [false, [
                'name' => 'Test Trader',
                'market' => 1,
                'users' => [
                    10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                    11 => ['name' => 'Test Name 2', 'email' => 'valid@example.com'],
                ],
            ]],
        ];
    }
}

<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\Http\Requests\AdminUpdateCentreRequest;
use App\Sponsor;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\StoreTestCase;
use Closure;

class AdminUpdateCentreRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new AdminUpdateCentreRequest())->rules();
        factory(Centre::class)->create(['name' => 'EXIST', 'prefix' => 'EXIST']);
        factory(Sponsor::class)->create();
    }

    private function validate(array $mockedRequestData): bool
    {
        return Validator::make($mockedRequestData, $this->rules)->passes();
    }

    private function validateAsUpdate(Centre $centre, array $data): bool
    {
        $request = new AdminUpdateCentreRequest();
        $request->setRouteResolver(function () use ($centre) {
            return new class ($centre) {
                public function __construct(private readonly Centre $centre)
                {
                }

                public function parameter(string $name): mixed
                {
                    return $name === 'centre' ? $this->centre : null;
                }
            };
        });

        return Validator::make($data, $request->rules())->passes();
    }


    #[DataProvider('validationCases')]
    public function testItValidatesCentreRequests(
        bool $shouldPass,
        array $mockedRequestData,
        ?Closure $centreFn = null
    ): void {
        if ($centreFn) {
            $centre = $centreFn();
            $this->assertEquals($shouldPass, $this->validateAsUpdate($centre, $mockedRequestData));
        } else {
            $this->assertEquals($shouldPass, $this->validate($mockedRequestData));
        }
    }

    public static function validationCases(): Generator
    {
        yield 'Valid request' => [
            true,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Missing name' => [
            false,
            [
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Name is not a string' => [
            false,
            [
                'name' => 1,
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Name already exists' => [
            false,
            [
                'name' => 'EXIST',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Missing sponsor' => [
            false,
            [
                'name' => 'Test Centre',
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Sponsor is not an integer' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 'not an integer',
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Invalid sponsor' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 999,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Missing RVID prefix' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'RVID is not a string' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 1,
                'print_pref' => 'individual',
            ],
        ];

        yield 'RVID is less than one character' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => '',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'RVID is more than five characters' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'ABCDEF',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'RVID already exists' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'EXISTS',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
        ];

        yield 'Missing print preference' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'ABCDEF',
                'can_collect' => false,
            ],
        ];

        yield 'Invalid print preference' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'ABCDEF',
                'print_pref' => 'not even slightly a print pref',
                'can_collect' => false,
            ],
        ];

        yield 'can_collect can be true' => [
            true,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => true,
            ],
        ];

        yield 'can_collect might be absent' => [
            true,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
            ],
        ];

        yield 'can_collect might be null' => [
            true,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => null,
            ],
        ];

        yield 'can_collect must be boolean' => [
            false,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => 'true',
            ],
        ];

        yield 'Centre can keep its own name' => [
            true,
            [
                'name' => 'EXIST',
                'sponsor_id' => 1,
                'prefix' => 'TSTCT',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
            function () {
                return Centre::where('name', 'EXIST')->first();
            },
        ];

        yield 'Centre can keep its own prefix' => [
            true,
            [
                'name' => 'Test Centre',
                'sponsor_id' => 1,
                'prefix' => 'EXIST',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
            function () {
                return Centre::where('prefix', 'EXIST')->first();
            },
        ];

        yield 'Centre can keep both its own name and prefix' => [
            true,
            [
                'name' => 'EXIST',
                'sponsor_id' => 1,
                'prefix' => 'EXIST',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
            function () {
                return Centre::where('name', 'EXIST')->first();
            },
        ];

        yield 'Another centres name is still rejected' => [
            false,
            [
                'name' => 'EXIST',
                'sponsor_id' => 1,
                'prefix' => 'OTHER',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
            function () {
                return factory(Centre::class)->create(['name' => 'Other Centre', 'prefix' => 'OTHER']);
            },
        ];

        yield 'Another centres prefix is still rejected' => [
            false,
            [
                'name' => 'Other Centre',
                'sponsor_id' => 1,
                'prefix' => 'EXIST',
                'print_pref' => 'individual',
                'can_collect' => false,
            ],
            function () {
                return factory(Centre::class)->create(['name' => 'Other Centre', 'prefix' => 'OTHER']);
            },
        ];
    }
}

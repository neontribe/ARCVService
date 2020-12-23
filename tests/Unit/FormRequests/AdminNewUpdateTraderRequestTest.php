<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminNewUpdateTraderRequest;
use App\Market;
use App\Sponsor;
use App\Trader;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminNewUpdateTraderRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');

        $sponsor = factory(Sponsor::class)->create();

        $market = factory(Market::class)->create(['sponsor_id' => $sponsor->id]);

        factory(Trader::class)->create(['market_id' => $market->id]);
    }

    /**
     * General validator
     * @param $mockedRequestData
     * @param $rules
     * @return mixed
     */
    protected function validate($mockedRequestData, $rules)
    {
        return $this->validator
            ->make($mockedRequestData, $rules)
            ->passes();
    }

    /**
     * @test
     * @dataProvider storeValidationProvider
     * @param bool $shouldPass
     * @param array $mockedRequestData
     */
    public function testICannotSubmitInvalidValues($shouldPass, $mockedRequestData)
    {
        // Copy the rules out of the FormRequest.
        $rules = (new AdminNewUpdateTraderRequest())->rules();

        $this->assertEquals(
            $shouldPass,
            $this->validate($mockedRequestData, $rules)
        );
    }

    /**
     * must return hardcoded values
     * @return array
     */
    public function storeValidationProvider()
    {
        // We need to do this as this provider gets made before laravel normally gets out of bed.
        $this->createApplication();

        return [
            'requestShouldSucceedWhenMinRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                ]
            ],
            'requestShouldSucceedWhenOptionalProvidedAsBooleanInt' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'disabled' => 1,
                ]
            ],
            'requestShouldSucceedWhenOptionalDataProvidedAsNull' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'disabled' => null,
                ]
            ],
            'requestShouldSucceedWhenOptionalDataProvidedAsBoolean' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'disabled' => false,
                ]
            ],
            'requestShouldSucceedWhenOptionalDataProvidedAsBooleanText' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'disabled' => "0",
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                'passed' => false,
                'data' => [
                    'market' => 1,
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 1,
                    'market' => 1,
                ]
            ],
            'requestShouldFailWhenNameIsMoreThan160Chars' => [
                'passed' => false,
                'data' => [
                    'market' => 1,
                    'name' => '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1'
                    ,
                ]
            ],
            'requestShouldPassWhenNameIsLessThan161Chars' => [
                'passed' => true,
                'data' => [
                    'market' => 1,
                    'name' => '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890'
                    ,
                ]
            ],
            'requestShouldFailWhenMarketIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenMarketIsNotInteger' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 'f',
                ]
            ],
            'requestShouldFailWhenMarketIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 999,
                ]
            ],
            'requestShouldSucceedWhenMinRequiredUserDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                    ]
                ]
            ],
            'requestShouldFailWhenUsersIsNotAnArray' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => "thing"
                ]
            ],
            'requestShouldFailWhenUsersIsAnEmptyAnArray' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => []
                ]
            ],
            'requestShouldFailWhenUserNameDataIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 1, 'email' => 'valid@example.com'],
                    ]
                ]
            ],
            'requestShouldSucceedWhenUserNameDataIsLTE160' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => [
                            'name' => '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890',
                            'email' => 'valid@example.com'
                        ],
                    ]
                ]
            ],
            'requestShouldFailWhenUserNameDataIsGT160' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => [
                            'name' => '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890' .
                                '1234567890123456789012345678901234567890' .
                                '1',
                            'email' => 'valid@example.com'
                        ],
                    ]
                ]
            ],
            'requestShouldFailWhenUserNameDataIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['email' => 'valid@example.com'],
                    ]
                ]
            ],
            'requestShouldFailWhenUserEmailDataIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 'Test Name'],
                    ]
                ]
            ],
            'requestShouldFailWhenUserEmailIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 'Test Name', 'email' => 'notAValidMail.com'],
                    ]
                ]
            ],
            'requestShouldSucceedWhenMultiRequiredUserDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                        11 => ['name' => 'Test Name 2', 'email' => 'valid2@example.com'],
                    ]
                ]
            ],
            'requestShouldFailWhenMultiUserDataIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['email' => 'valid@example.com'],
                        11 => ['name' => 'Test Name 2'],
                    ]
                ]
            ],
            'requestShouldFailWhenMultiUserEmailsAreDuplicated' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Trader',
                    'market' => 1,
                    'users' => [
                        10 => ['name' => 'Test Name', 'email' => 'valid@example.com'],
                        11 => ['name' => 'Test Name 2', 'email' => 'valid@example.com'],
                    ]
                ]
            ],

        ];
    }
}

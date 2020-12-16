<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminNewUpdateMarketRequest;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminNewUpdateMarketRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        // make factory 1
        factory(Sponsor::class)->create();
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
        $rules = (new AdminNewUpdateMarketRequest())->rules();

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
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                'passed' => false,
                'data' => [
                    'sponsor' => 1,
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 1,
                    'sponsor' => 1,
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenSponsorIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenSponsorIsNotInteger' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 'f',
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenSponsorIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 999,
                    'payment_message' => 'a message',
                ]
            ],
            'requestShouldFailWhenPaymentMessageIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                ]
            ],
            'requestShouldFailWhenPaymentMessageIsNotAString' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                    'payment_message' => 1,
                ]
            ],
            'requestShouldFailWhenPaymentMessageIsLessThanOneChar' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                    'payment_message' => '',
                ]
            ],
            'requestShouldFailWhenPaymentMessageIsMoreThan160Chars' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                    'payment_message' => '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1'
                    ,
                ]
            ],
            'requestShouldPassWhenPaymentMessageIsLessThan161Chars' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Market',
                    'sponsor' => 1,
                    'payment_message' => '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890' .
                        '1234567890123456789012345678901234567890'
                    ,
                ]
            ]
        ];
    }
}

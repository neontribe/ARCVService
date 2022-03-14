<?php


namespace Tests\Unit\FormRequests;

use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Trader;
use App\User;
use App\Voucher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class ApiTransitionVoucherRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    /** @var Trader */
    private $trader;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');

        $this->trader = factory(Trader::class)->create();
    }

    /**
     * General validator
     * @param $mockedRequestData
     * @param $rules
     * @return mixed
     */
    protected function validate($mockedRequestData, $rules)
    {
        return $this->validator->make($mockedRequestData, $rules)->passes();
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
        $rules = (new ApiTransitionVoucherRequest())->rules();

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
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'trader_id' => 1,
                    'transition' => 'collect',
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhenRequiredDataIsMissing' => [
                'passed' => false,
                'data' => []
            ],
            'requestShouldFailWhenTraderIsMissing' => [
                'passed' => false,
                'data' => [
                    'transition' => 'collect',
                    'vouchers' => ['code1'],
                ]
            ],
            'requestShouldFailWhenTraderIsNull' => [
                'passed' => false,
                'data' => [
                    'trader_id' => null,
                    'transition' => 'collect',
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhenTraderIsNotInDB' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 2,
                    'transition' => 'collect',
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhentransitionIsMissing' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhenTransitionIsNull' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'transition' => null,
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhenTransitionIsNotAString' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'transition' => 12345,
                    'vouchers' => ['code1', 'code2'],
                ]
            ],
            'requestShouldFailWhenVouchersIsMissing' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'transition' => 'collect',
                ]
            ],
            'requestShouldFailWhenVouchersIsEmpty' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'transition' => 'collect',
                    'vouchers' => [],
                ]
            ],
            'requestShouldFailWhenVouchersIsNotAnArray' => [
                'passed' => false,
                'data' => [
                    'trader_id' => 1,
                    'transition' => 'collect',
                    'vouchers' => "fish",
                ]
            ]
        ];
    }
}

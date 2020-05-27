<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminUpdateVoucherRequest;
use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminUpdateVoucherRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    public function setUp()
    {
        parent::setUp();
        $this->validator = app()->get('validator');

        $rangeCodes = [
            'TST0101',
            'TST0102',
            'TST0103',
            'TST0104',
            'TST0105',
        ];

        // Make a sponsor to match
        $sponsor = factory(Sponsor::class)->create(
            ['shortcode' => 'TST']
        );

        // Make an error Sponsor
        $err_sponsor = factory(Sponsor::class)->create(
            ['shortcode' => 'ERR']
        );

        // TODO: Looks like voucher transitioning might be dependent on trader users
        $user = factory(User::class)->create();

        Auth::login($user);

        // Batch make those vouchers
        foreach ($rangeCodes as $rangeCode) {
            factory(Voucher::class, 'printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $sponsor->id,
            ]);
        }

        // Make the error voucher
        factory(Voucher::class, 'printed')->create([
            'code' => 'ERR0105',
            'sponsor_id' => $err_sponsor->id,
        ]);

        Auth::logout();
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
        $rules = (new AdminUpdateVoucherRequest())->rules();

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
            'requestShouldVoidWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'transition' => 'void',
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldExpireWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldFailWhenTransitionIsMissing' => [
                'passed' => false,
                'data' => [
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldFailWhenTransitionIsNotString' => [
                'passed' => false,
                'data' => [
                    'transition' => 1,
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldFailWhenVoucherStartIsMissing' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldFailWhenVoucherEndIsMissing' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0101',
                ]
            ],
            'requestShouldFailWhenVoucherStartIsLargerThanVoucherEnd' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0105',
                    'voucher-end' => 'TST0101',
                ]
            ],
            'requestShouldFailWhenVoucherStartIsNotValid' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0100',
                    'voucher-end' => 'TST0105',
                ]
            ],
            'requestShouldFailWhenVoucherEndIsNotValid' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'TST0106',
                ]
            ],
            'requestShouldFailWhenVoucherPrefixesAreDifferent' => [
                'passed' => false,
                'data' => [
                    'transition' => 'expire',
                    'voucher-start' => 'TST0101',
                    'voucher-end' => 'ERR0105',
                ]
            ],
        ];
    }
}

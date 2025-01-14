<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\StoreNewRegistrationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class StoreNewRegistrationRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var Validator */
    private $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');
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
        $rules = (new StoreNewRegistrationRequest())->rules();

        $this->assertEquals(
            $shouldPass,
            $this->validate($mockedRequestData, $rules)
        );
    }

    /**
     * must return hardcoded values
     * @return array
     */
    public static function storeValidationProvider(): array
    {
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                true,
                [
                    'consent' => 'yes',
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWhenRequiredDataIsMissing' => [
                false,
                []
            ],
            'requestShouldFailWhenConsentIsMissing' => [
                false,
                [
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            // this can now pass when eligibility is missing due to SP
            'requestCanPassWhenEligibilityIsMissing' => [
                true,
                [
                    'consent' => 'on',
                    'pri_carer' => 'A String',
                ]
            ],
            'requestShouldFailWhenCarerIsNotAString' => [
                false,
                [
                    'consent' => true,
                    'pri_carer' => 1,
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWhenEligibilityIsNotInEnum' => [
                false,
                [
                    'consent' => 'yes',
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'hello',
                    'eligibility-nrpf' => 'hello'
                ]
            ],
            'requestShouldFailWhenConsentIsNotTruthy' => [
                false,
                [
                    'consent' => 2,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldSucceedWithSecondaryCarers' => [
                true,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'carers' => ['B String', 'C String'],
                ]
            ],
            'requestShouldFailWithEmptySecondaryCarers' => [
                false,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'new_carers' => [],
                ]
            ],
            'requestShouldFailWithNonStringSecondaryCarers' => [
                false,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'new_carers' => [1,2,3,4],
                ]
            ],
            'requestShouldSucceedWithMinimalChildren' => [
                true,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [
                        0 => ['dob' => '2017-09']
                    ],
                ]
            ],
            'requestShouldFailWithChildInvalidDobFormat' => [
                false,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [
                        0 => ['dob' => '2017-09-01']
                    ],
                ]
            ],
            'requestShouldFailWithEmptyChildren' => [
                false,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [],
                ]
            ],
            'requestShouldSucceedWithManyChildren' => [
                true,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [
                        0 => ['dob' => '2017-09'],
                        1 => ['dob' => '2016-08'],
                        2 => ['dob' => '2015-07']
                    ],
                ]
            ],
            'requestShouldSucceedWithManyVerifiableChildren' => [
                true,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [
                        0 => [
                            'dob' => '2017-09',
                            'verified' => true
                        ],
                        1 => [
                            'dob' => '2016-08',
                            'verified' => true
                        ],
                        2 => [
                            'dob' => '2015-07',
                            'verified' => false
                        ],
                    ],
                ]
            ],
            'requestShouldFailWhenAVerifiableChildHasNoDoB' => [
                false,
                [
                    'consent' => 1,
                    'pri_carer' => 'A String',
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes',
                    'children' => [
                        0 => [
                            'dob' => '2017-09',
                            'verified' => true
                        ],
                        1 => [
                            // NO DoB - fail!
                            'verified' => true
                        ],
                        2 => [
                            'dob' => '2015-07',
                            'verified' => false
                        ]
                    ],
                ]
            ],
        ];
    }
}

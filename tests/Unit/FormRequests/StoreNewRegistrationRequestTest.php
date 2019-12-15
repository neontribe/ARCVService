<?php

namespace Tests\Unit\FormRequests;

use App\Registration;
use App\Http\Requests\StoreNewRegistrationRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class StoreNewRegistrationRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    public function setUp()
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
    public function storeValidationProvider()
    {
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'consent' => 'yes',
                    'carer' => 'A String',
                    'eligibility' => 'other'
                ]
            ],
            'requestShouldFailWhenRequiredDataIsMissing' => [
                'passed' => false,
                'data' => []
            ],
            'requestShouldFailWhenConsentIsMissing' => [
                'passed' => false,
                'data' => [
                    'carer' => 'A String',
                    'eligibility' => 'other'
                ]
            ],
            'requestShouldFailWhenEligibilityIsMissing' => [
                'passed' => false,
                'data' => [
                    'consent' => 'on',
                    'carer' => 'A String',
                ]
            ],
            'requestShouldFailWhenCarerIsNotAString' => [
                'passed' => false,
                'data' => [
                    'consent' => true,
                    'carer' => 1,
                    'eligibility' => 'other'
                ]
            ],
            'requestShouldFailWhenEligibilityIsNotInEnum' => [
                'passed' => false,
                'data' => [
                    'consent' => 'yes',
                    'carer' => 'A String',
                    'eligibility' => 'hello'
                ]
            ],
            'requestShouldFailWhenConsentIsNotTruthy' => [
                'passed' => false,
                'data' => [
                    'consent' => 2,
                    'carer' => 'A String',
                    'eligibility' => 'healthy-start'
                ]
            ],
            'requestShouldSucceedWithSecondaryCarers' => [
                'passed' => true,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'carers' => ['B String', 'C String'],
                ]
            ],
            'requestShouldFailWithEmptySecondaryCarers' => [
                'passed' => false,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'carers' => [],
                ]
            ],
            'requestShouldFailWithNonStringSecondaryCarers' => [
                'passed' => false,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'carers' => [1,2,3,4],
               ]
            ],
            'requestShouldSucceedWithMinimalChildren' => [
                'passed' => true,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'children' => [
                        0 => ['dob' => '2017-09']
                    ],
                ]
            ],
            'requestShouldFailWithChildInvalidDobFormat' => [
                'passed' => false,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'children' => [
                        0 => ['dob' => '2017-09-01']
                    ],
                ]
            ],
            'requestShouldFailWithEmptyChildren' => [
                'passed' => false,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'children' => [],
                ]
            ],
            'requestShouldSucceedWithManyChildren' => [
                'passed' => true,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
                    'children' => [
                        0 => ['dob' => '2017-09'],
                        1 => ['dob' => '2016-08'],
                        2 => ['dob' => '2015-07']
                    ],
                ]
            ],
            'requestShouldSucceedWithManyVerifiableChildren' => [
                'passed' => true,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
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
                'passed' => false,
                'data' => [
                    'consent' => 1,
                    'carer' => 'A String',
                    'eligibility' => 'other',
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

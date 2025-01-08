<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\StoreUpdateRegistrationRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class StoreUpdateRegistrationRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

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
        $rules = (new StoreUpdateRegistrationRequest())->rules();

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
                    'pri_carer' => ['A String'],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ],
            ],
            // this can now pass when eligibility is missing due to SP
            'requestCanPassWhenEligibilityIsMissing' => [
                true,
                [
                    'pri_carer' => ['A String'],
                ]
            ],
            'requestShouldFailWhenRequiredDataIsMissing' => [
                false,
                []
            ],
            'requestShouldFailWhenTooManyPriCarers' => [
                false,
                [
                    'pri_carer' => ['A String', 'B String'],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWhenNoPriCarers' => [
                false,
                [
                    'pri_carer' => [],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWhenNonStringPriCarers' => [
                false,
                [
                    'pri_carer' => [1],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldSucceedWithSecondaryCarers' => [
                true,
                [
                    'pri_carer' => ['A String'],
                    'sec_carers' => ['B String', 'C String'],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithEmptySecondaryCarers' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'sec_carers' => [],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithNonStringSecondaryCarers' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'sec_carers' => [1,2,3,4],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldSucceedWithNewCarers' => [
                true,
                [
                    'pri_carer' => ['A String'],
                    'new_carers' => ['B String', 'C String'],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithEmptyNewCarers' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'new_carers' => [],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithNonStringNewCarers' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'new_carers' => [1,2,3,4],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldSucceedWithMinimalChildren' => [
                true,
                [
                    'pri_carer' => ['A String'],
                    'children' => [
                        0 => ['dob' => '2017-09']
                    ],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithChildInvalidDobFormat' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'children' => [
                        0 => ['dob' => '2017-09-01']
                    ],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWithEmptyChildren' => [
                false,
                [
                    'pri_carer' => ['A String'],
                    'children' => [],
                ]
            ],
            'requestShouldSucceedWithManyChildren' => [
                true,
                [
                    'pri_carer' => ['A String'],
                    'children' => [
                        0 => ['dob' => '2017-09'],
                        1 => ['dob' => '2016-08'],
                        2 => ['dob' => '2015-07']
                    ],
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldSucceedWithManyVerifiableChildren' => [
                true,
                [
                    'pri_carer' => ['A String'],
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
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
            'requestShouldFailWhenAVerifiableChildHasNoDoB' => [
                false,
                [
                    'pri_carer' => ['A String'],
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
                    'eligibility-hsbs' => 'healthy-start-applying',
                    'eligibility-nrpf' => 'yes'
                ]
            ],
        ];
    }
}

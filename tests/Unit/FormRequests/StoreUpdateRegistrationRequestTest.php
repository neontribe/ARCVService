<?php

namespace Tests\Unit\FormRequests;

use App\Registration;
use App\Http\Requests\StoreUpdateRegistrationRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class StoreUpdateRegistrationRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    public function setUp()
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        factory(Registration::class)->create();
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
    public function storeValidationProvider()
    {
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'pri_carer' => ['A String'],
                ]
            ],
            'requestShouldFailWhenRequiredDataIsMissing' => [
                'passed' => false,
                'data' => []
            ],
            'requestShouldFailWhenTooManyPriCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String', 'B String'],
                ]
            ],
            'requestShouldFailWhenNoPriCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => [],
                ]
            ],
            'requestShouldFailWhenNonStringPriCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => [1],
                ]
            ],
            'requestShouldSucceedWithSecondaryCarers' => [
                'passed' => true,
                'data' => [
                    'pri_carer' => ['A String'],
                    'sec_carers' => ['B String', 'C String'],
                ]
            ],
            'requestShouldFailWithEmptySecondaryCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String'],
                    'sec_carers' => [],
                ]
            ],
            'requestShouldFailWithNonStringSecondaryCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String'],
                    'sec_carers' => [1,2,3,4],
                ]
            ],
            'requestShouldSucceedWithNewCarers' => [
                'passed' => true,
                'data' => [
                    'pri_carer' => ['A String'],
                    'new_carers' => ['B String', 'C String'],
                ]
            ],
            'requestShouldFailWithEmptyNewCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String'],
                    'new_carers' => [],
                ]
            ],
            'requestShouldFailWithNonStringNewCarers' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String'],
                    'new_carers' => [1,2,3,4],
                ]
            ],
            'requestShouldSucceedWithMinimalChildren' => [
                'passed' => true,
                'data' => [
                    'pri_carer' => ['A String'],
                    'children' => [
                        0 => ['dob' => '2017-09']
                    ],
                ]
            ],
            'requestShouldFailWithEmptyChildren' => [
                'passed' => false,
                'data' => [
                    'pri_carer' => ['A String'],
                    'children' => [],
                ]
            ],
            'requestShouldSucceedWithManyChildren' => [
                'passed' => true,
                'data' => [
                    'pri_carer' => ['A String'],
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
                ]
            ],
            'requestShouldFailWhenAVerifiableChildHasNoDoB' => [
                'passed' => false,
                'data' => [
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
                ]
            ],
        ];
    }
}

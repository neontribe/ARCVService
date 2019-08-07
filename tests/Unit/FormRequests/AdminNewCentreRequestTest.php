<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminNewCentreRequest;
use App\Sponsor;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminNewCentreRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    /** @var array */
    private $print_prefs;

    public function setUp()
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        $this->print_prefs = array_random(config('arc.print_preferences'));
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
        $rules = (new AdminNewCentreRequest())->rules();

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
        $prefs = array_random(config('arc.print_preferences'));

        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                'passed' => false,
                'data' => [
                    'sponsor' => 1,
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 1,
                    'sponsor' => 1,
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenSponsorIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenSponsorIsNotInteger' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 'not an integer',
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenSponsorIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 2,
                    'rvid_prefix' => 'TSTCT',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenRvidIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenRvidIsNotAString' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 1,
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenRvidIsLessThanOneChar' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => '',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenRvidIsMoreThan5Chars' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 'ABCDEF',
                    'print_pref' => $prefs
                ]
            ],
            'requestShouldFailWhenPrintPrefIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 'ABCDEF',
                ]
            ],
            'requestShouldFailWhenPrintPrefIsNotInList' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 'ABCDEF',
                    'print_pref' => 'not even slightly a print pref'
                ]
            ],
        ];
    }
}

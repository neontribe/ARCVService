<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\Http\Requests\AdminNewCentreRequest;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminNewCentreRequestTest extends StoreTestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    /** @var Validator */
    private $validator;

    /** @var array $print_prefs */
    private $print_prefs;

    /** @var Centre $existingCentre */
    private $existingCentre;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        $this->print_prefs = array_random(config('arc.print_preferences'));
        $this->existingCentre = factory(Centre::class)->create([
           'prefix' => 'EXISTS',
        ]);
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
                    'sponsor' => 999,
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
            'requestShouldFailWhenRvidExists' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test Centre',
                    'sponsor' => 1,
                    'rvid_prefix' => 'EXISTS',
                    'print_pref' => 'not even slightly a print pref'
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

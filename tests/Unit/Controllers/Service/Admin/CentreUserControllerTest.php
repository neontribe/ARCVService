<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Http\Requests\AdminNewCentreUserRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class CentreUserControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    private $centre;

    private $altCentres;

    public function setUp()
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        $this->centre = factory(Centre::class)->create([]);
        $this->altCentres = factory(Centre::class, 3)->create([]);
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
        $alternatives = (isset($mockedRequestData['alternative_centres']))
            ? $mockedRequestData['alternative_centres']
            : null;

        // Copy the rules out of the FormRequest.
        $rules = (new AdminNewCentreUserRequest())->rules($alternatives);

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
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                'passed' => false,
                'data' => [
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 1,
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenEmailIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenEmailIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'notAnEmail',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenCentreIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk'
                ]
            ],
            'requestShouldFailWhenCentreIsInvalid' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 100
                ]
            ],
            'requestShouldFailWhenCentreIsNotInteger' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => "not an integer"
                ]
            ],
            'requestShouldPassWhenItHasValidAlternatives' => [
                'passed' => true,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [2,3,4]
                ]
            ],
            'requestShouldPassWhenItHasAlternativesThatAreNotValidCentres' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [2,3,5]
                ]
            ],
            'requestShouldFailWhenAlternativesAreNotIntegers' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => ["not an integer", "me neither"]
                ]
            ],
            'requestShouldFailWhenCentreIsInAlternatives' => [
                'passed' => false,
                'data' => [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [1,3,4]
                ]
            ],
        ];
    }

    /** @test */
    public function testICanStoreACentreUser()
    {
        $adminUser = factory(AdminUser::class)->create();

        $data = [
            'name' => 'bobby testee',
            'email' => 'bobby@test.co.uk',
            'worker_centre' => $this->centre->id,
            'alternative_centres.*' => $this->altCentres->pluck('id')->all()
        ];

        $this->actingAs($adminUser, 'admin')
            ->post(
                route('admin.centreusers.store'),
                $data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.centreusers.index'))
            ->see($data["name"])
            ->see($data["email"])
        ;
        // find the user
        $cu = CentreUser::where('email', $data['email'])->first();
        $this->assertNotNull($cu);
        // Check the neighbours
        $this->assertEquals(4, $cu->relevantCentres()->count());
        // Check the homeCentre
        $this->assertEquals($data['worker_centre'], $cu->homeCentre->id);
    }
}

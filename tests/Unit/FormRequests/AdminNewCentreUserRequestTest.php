<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\Http\Requests\AdminNewCentreUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminNewCentreUserRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        factory(Centre::class, 4)->create([]);
    }

    /**
     * General validator
     * @param $mockedRequestData
     * @param $rules
     * @return bool
     */
    protected function validate($mockedRequestData, $rules): bool
    {
        return Validator::make($mockedRequestData, $rules)->passes();
    }

    /**
     * @dataProvider storeValidationProvider
     * @param bool $shouldPass
     * @param array $mockedRequestData
     */
    public function testICannotSubmitInvalidValues(bool $shouldPass, array $mockedRequestData): void
    {
        $alternatives = $mockedRequestData['alternative_centres'] ?? null;

        // Copy the rules out of the FormRequest.
        $rules = (new AdminNewCentreUserRequest())->rules($alternatives);

        $this->assertEquals(
            $shouldPass,
            $this->validate($mockedRequestData, $rules)
        );
    }

    /**
     * must return hardcoded values
     * @return array[]
     */
    public static function storeValidationProvider(): array
    {
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                true,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                false,
                [
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                false,
                [
                    'name' => 1,
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenEmailIsMissing' => [
                false,
                [
                    'name' => 'bobby testee',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenEmailIsInvalid' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'notAnEmail',
                    'worker_centre' => 1
                ]
            ],
            'requestShouldFailWhenCentreIsMissing' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk'
                ]
            ],
            'requestShouldFailWhenCentreIsInvalid' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 100
                ]
            ],
            'requestShouldFailWhenCentreIsNotInteger' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => "not an integer"
                ]
            ],
            'requestShouldPassWhenItHasValidAlternatives' => [
                true,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [2, 3, 4]
                ]
            ],
            'requestShouldPassWhenItHasAlternativesThatAreNotValidCentres' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [2, 3, 5]
                ]
            ],
            'requestShouldFailWhenAlternativesAreNotIntegers' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => ["not an integer", "me neither"]
                ]
            ],
            'requestShouldFailWhenCentreIsInAlternatives' => [
                false,
                [
                    'name' => 'bobby testee',
                    'email' => 'bobby@test.co.uk',
                    'worker_centre' => 1,
                    'alternative_centres' => [1, 3, 4]
                ]
            ],
        ];
    }
}

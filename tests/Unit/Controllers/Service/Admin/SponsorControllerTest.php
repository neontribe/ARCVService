<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Http\Controllers\Service\Admin\SponsorsController;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SponsorControllerTest extends StoreTestCase
{
    use RefreshDatabase;

    /** @var Validator */
    private $validator;

    /** @var Sponsor */
    private $existingSponsor;
    private $socialPrescribingSponsor;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = app()->get('validator');
        $this->existingSponsor = factory(Sponsor::class)->create([
           'shortcode' => 'EXIST'
        ]);

        $this->socialPrescribingSponsor = factory(Sponsor::class)->create([
            'name' => "Social Prescribing Area",
            'shortcode' => "SPA",
            'programme' => 1
        ]);
    }

    /**
     * General validator
     * @param $mockedRequestData
     * @param $rules
     * @return mixed
     */
    protected function validate($mockedRequestData, $rules): mixed
    {
        return $this->validator
            ->make($mockedRequestData, $rules)
            ->passes();
    }

    /**
     * @param bool $shouldPass
     * @param array $mockedRequestData
     */
    #[DataProvider('storeValidationProvider')]
    public function testICannotSubmitInvalidValues(bool $shouldPass, array $mockedRequestData): void
    {
        $rules = (new AdminNewSponsorRequest())->rules();

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
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'TSTSR',
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                false,
                [
                    'voucher_prefix' => 'TSTSR',
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                false,
                [
                    'name' => 1,
                    'voucher_prefix' => 'TSTSR',
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenVoucherPrefixIsMissing' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenVoucherPrefixIsNotString' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 1,
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenVoucherPrefixExists' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'EXIST',
                    'programme' => 0,
                ]
            ],
            'requestShouldFailWhenProgrammeIsMissing' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'TSTSR',
                ]
            ],
            'requestShouldFailWhenProgrammeIsNotNumeric' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'TSTSR',
                    'programme' => 'one',
                ]
            ],

            'requestShouldFailWhenProgrammeIsNotEnmerable' => [
                false,
                [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'TSTSR',
                    # should not exist
                    'programme' => 999,
                ]
            ]
        ];
    }


    public function testICanStoreASponsor(): void
    {
        $adminUser = factory(AdminUser::class)->create();

        // Set some data
        $data = [
            'name' => 'Test-shire Sponsor',
            'voucher_prefix' => 'TSTSR',
            'programme' => 0
        ];

        // Check can add a Sponsor
        $this->actingAs($adminUser, 'admin')
            ->post(
                route('admin.sponsors.store'),
                $data
            )
            ->followRedirects()
            ->assertResponseOk()
            ->seePageIs(route('admin.sponsors.index'))
            ->see($data["name"])
            ->see($data["voucher_prefix"])
        ;

        // Find the Sponsor, and check it's attributes
        $this->seeInDatabase('sponsors', [
            'name' => $data["name"],
            'shortcode' => $data['voucher_prefix'],
            // controller sets this false
            'can_tap' => false,
            'programme' => 0
        ]);
    }


    public function testItRedirectsBackOnError(): void
    {
        $adminUser = factory(AdminUser::class)->create();

        // Send some bad data
        $badData = [
            'name' => 'Test-shire Sponsor',
            'voucher_prefix' => 'EXIST',
        ];
        // Check can add a Sponsor
        $this->actingAs($adminUser, 'admin')
            ->visit(route('admin.sponsors.create'))
            ->post(
                route('admin.sponsors.store'),
                $badData
            )
            ->assertRedirectedTo(route('admin.sponsors.create'))
        ;
        // Find the Sponsor
        $this->dontSeeInDatabase('sponsors', [
            'name' => $badData["name"],
            'shortcode' => $badData['voucher_prefix']
        ]);
    }


    public function testICanSeeProgrammeTypeOnSponsorListPage(): void
    {
        $adminUser = factory(AdminUser::class)->create();
        $this->actingAs($adminUser, 'admin')
            ->visit(route('admin.sponsors.index'))
            ->see($this->socialPrescribingSponsor->id)
            ->see($this->existingSponsor->id)
            ->see('sponsors/' . $this->socialPrescribingSponsor->id)
            ->dontSee('sponsors/' . $this->existingSponsor->id)
        ;
    }


    public function testIEditRuleValuesForAnSPSponsor(): void
    {
        $socialPrescribingRules = SponsorsController::socialPrescribingOverrides();
        $this->socialPrescribingSponsor->evaluations()->saveMany($socialPrescribingRules);

        $adminUser = factory(AdminUser::class)->create();

        $this->seeInDatabase('evaluations', [
            'sponsor_id' => $this->socialPrescribingSponsor->id,
            'name' => 'HouseholdExists',
            'value' => 10
        ]);
        $this->seeInDatabase('evaluations', [
            'sponsor_id' => $this->socialPrescribingSponsor->id,
            'name' => 'HouseholdMember',
            'value' => 7
        ]);
        $this->seeInDatabase('evaluations', [
            'sponsor_id' => $this->socialPrescribingSponsor->id,
            'name' => 'DeductFromCarer',
            'value' => -7
        ]);

        $this->actingAs($adminUser, 'admin')
            ->visit(route('admin.sponsors.index'))
            ->click('Edit')
            ->seePageIs(route('admin.sponsors.edit', ['id' => $this->socialPrescribingSponsor->id]))
            ->type(15, 'householdExistsValue')
            ->type(4, 'householdMemberValue')
            ->press('Save');

        $this->seeInDatabase('evaluations', [
                'sponsor_id' => $this->socialPrescribingSponsor->id,
                'name' => 'HouseholdExists',
                'value' => 15
            ])
            ->seeInDatabase('evaluations', [
                'sponsor_id' => $this->socialPrescribingSponsor->id,
                'name' => 'HouseholdMember',
                'value' => 4
            ])
            ->seeInDatabase('evaluations', [
                'sponsor_id' => $this->socialPrescribingSponsor->id,
                'name' => 'DeductFromCarer',
                'value' => -4
            ])
        ;
    }


    public function testIEvaluationsAreCreatedIfTheyDoNotExistWhenIUpdate(): void
    {
        $adminUser = factory(AdminUser::class)->create();
        $sponsor = $this->socialPrescribingSponsor;

        // No Evaluations
        $this->dontSeeInDatabase('evaluations', ['sponsor_id' => $sponsor->id]);

        // Visit form and submit new evaluation values
        $this->actingAs($adminUser, 'admin')
            ->visit(route('admin.sponsors.index'))
            ->click('Edit')
            ->seePageIs(route('admin.sponsors.edit', ['id' => $sponsor->id]))
            ->type(20, 'householdExistsValue')
            ->type(5, 'householdMemberValue')
            ->press('Save');

        // Assert all evaluations are created correctly
        $this->seeInDatabase('evaluations', [
            'sponsor_id' => $sponsor->id,
            'name' => 'HouseholdExists',
            'value' => 20
        ])
            ->seeInDatabase('evaluations', [
                'sponsor_id' => $sponsor->id,
                'name' => 'HouseholdMember',
                'value' => 5
            ])
            ->seeInDatabase('evaluations', [
                'sponsor_id' => $sponsor->id,
                'name' => 'DeductFromCarer',
                'value' => -5
            ]);
    }
}

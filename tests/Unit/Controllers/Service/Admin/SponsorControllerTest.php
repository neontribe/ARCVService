<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Http\Controllers\Service\Admin\SponsorsController;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class SponsorControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

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
        $socialPrescribingRules = SponsorsController::socialPrescribingOverrides();
        $this->socialPrescribingSponsor->evaluations()->saveMany($socialPrescribingRules);
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
    public function storeValidationProvider()
    {
        return [
            'requestShouldSucceedWhenRequiredDataIsProvided' => [
                'passed' => true,
                'data' => [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'TSTSR',
                ]
            ],
            'requestShouldFailWhenNameIsMissing' => [
                'passed' => false,
                'data' => [
                    'voucher_prefix' => 'TSTSR',                ]
            ],
            'requestShouldFailWhenNameIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 1,
                    'voucher_prefix' => 'TSTSR',
                ]
            ],
            'requestShouldFailWhenVoucherPrefixIsMissing' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test-shire Sponsor',
                ]
            ],
            'requestShouldFailWhenVoucherPrefixIsNotString' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 1,
                ]
            ],
            'requestShouldFailWhenVoucherPrefixExists' => [
                'passed' => false,
                'data' => [
                    'name' => 'Test-shire Sponsor',
                    'voucher_prefix' => 'EXIST',
                ]
            ],
        ];
    }

    /** @test */
    public function testICanStoreASponsor()
    {
        $adminUser = factory(AdminUser::class)->create();

        // Set some data
        $data = [
            'name' => 'Test-shire Sponsor',
            'voucher_prefix' => 'TSTSR',
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
        ]);
    }

    /** @test */
    public function testItRedirectsBackOnError()
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

    /** @test */
    public function testICanSeeProgrammeTypeOnSponsorListPage()
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

    /** @test */
    public function testIEditRuleValuesForAnSPSponsor()
    {
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
            ->press('Save')
            ->seeInDatabase('evaluations', [
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
}

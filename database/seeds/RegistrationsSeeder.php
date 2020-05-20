<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RegistrationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 2 Regs for each known user
        $this->createRegistrationForCentre(2, App\CentreUser::find(1)->centre);
        $this->createRegistrationForCentre(2, App\CentreUser::find(2)->centre);
        $this->createRegistrationForCentre(2, App\CentreUser::find(3)->centre);

        // Create 10 regs randomly
        factory(App\Registration::class, 10)->create();

        // One registration with our CC with an incative family.
        $inactive = factory(App\Registration::class)
            ->create();

        // We will have a better way of incorporating this into factories - but currenly families get created by Reg seeds.
        // So for now, this ensures we have one for testing.
        $family = $inactive->family;
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();

        // create 3 regs for a *new* centre (with no users)
        $this->createRegistrationForCentre(3);

        // create a registration with a bundle
        $bundle = factory(App\Bundle::class)->create();
        factory(App\Registration::class)->create()->bundles()->save($bundle);

        $testCentre = App\Centre::find(1);

        /*
        TODO: create/find a centre without SK rules for fam1 and fam4 that is not in same area as families 3, 5 and 6
        TODO: create/find a centre with SK rules for fam2 that is not in same area as families 3, 5 and 6
        TODO: create/find a centre with SK rules for fam3 that is in a different area from all other families
        TODO: create/find a centre with SK rules but without toggle for fam 5 that is in a different area from all other families
        TODO: create/find a centre with non SK rules for fam 6 that is in a different area from all other families
         */

        $familyData1 = [
            'carers' => [
                ['name' => '1MAY20a-VC2-CH1-HI-042019'],
                ['name' => 'MAY20b-VC2-CH1-HI-042019'],
            ],
            'children' => [
                ['age' => 'almostOne', 'state' => 'unverified'],
            ],
            'joined_on' => Carbon::create(2019, 4, 9),
        ];

        $familyData2 = [
            'carers' => [
                ['name' => '2MAY20-VC1-CH2-HI-122018']
            ],
            'children' => [
                ['age' => 'underOne', 'state' => 'unverified'],
                ['age' => 'underSchoolAge', 'state' => 'unverified'],
                ['age' => 'readyForSecondarySchool', 'state' => 'verified'],
            ],
            'joined_on' => Carbon::create(2018, 12, 14),
        ];

        $familyData3 = [
            'carers' => [
                ['name' => '3MAY20-VC1-CH6-HAS-112019'],
            ],
            'children' => [
                ['age' => 'underSchoolAge', 'state' => 'verified'],
                ['age' => 'underSchoolAge', 'state' => 'verified'],
                ['age' => 'readyForSchool', 'state' => 'unverified'],
                ['age' => 'readyForSecondarySchool', 'state' => 'unverified'],
                ['age' => 'readyForSecondarySchool', 'state' => 'verified'],
                ['age' => 'readyForSecondarySchool', 'state' => 'verified'],
            ],
            'joined_on' => Carbon::create(2019, 11, 22),
        ];


        $familyData4 = [
            'carers' => [
                ['name' => '4MAY20-VC1-CH1P-HA-012020'],
            ],
            'children' => [
                ['age' => 'underSchoolAge', 'state' => 'unverified'],
                ['age' => 'unbornChild', 'state' => 'verified'],
            ],
            'joined_on' => Carbon::create(2020, 1, 30),
        ];

        $familyData5 = [
            'carers' => [
                ['name' => '5MAY20-VC1-CH3-HA-112015'],
            ],
            'children' => [
                ['age' => 'readyForSecondarySchool', 'state' => 'verified'],
                ['age' => 'readyForSecondarySchool', 'state' => 'verified'],
                ['age' => 'underSchoolAge', 'state' => 'unverified'],
            ],
            'joined_on' => Carbon::create(2019, 11, 12),
        ];

        $familyData6 = [
            'carers' => [
                ['name' => '6MAY-VC1-CH2-HI-032020'],
            ],
            'children' => [
                ['age' => 'underOne', 'state' => 'unverified'],
                ['age' => 'underSchoolAge', 'state' => 'verified'],
            ],
            'joined_on' => Carbon::create(2020, 4, 1),
        ];

        $this->createRegistrationTestCase($familyData1, $testCentre);
        $this->createRegistrationTestCase($familyData2, $testCentre);
        $this->createRegistrationTestCase($familyData3, $testCentre);
        $this->createRegistrationTestCase($familyData4, $testCentre);
        $this->createRegistrationTestCase($familyData5, $testCentre);
        $this->createRegistrationTestCase($familyData6, $testCentre);
    }

    /**
     * This is horrible and there's a better way to mke seeds, imagine.
     * @param $quantity
     * @param Centre $centre
     * @return \Illuminate\Support\Collection
     */
    public function createRegistrationForCentre($quantity, App\Centre $centre = null)
    {
        // set the loop and centre
        $quantity = ($quantity) ? $quantity : 1;
        if (is_null($centre)) {
            $centre = factory(App\Centre::class)->create();
        }
        $registrations = [];

        $eligibilities = config('arc.reg_eligibilities');

        foreach (range(1, $quantity) as $q) {
            // create a family and set it up.
            $family = factory(App\Family::class)->make();
            $family->lockToCentre($centre);
            $family->save();
            $family->carers()->saveMany(factory(App\Carer::class, random_int(1, 3))->make());
            $family->children()->saveMany(factory(\App\Child::class, random_int(0, 4))->make());

            $registrations[] = App\Registration::create(
                [
                    'centre_id' => $centre->id,
                    'family_id' => $family->id,
                    'eligibility' => $eligibilities[mt_rand(0, count($eligibilities) - 1)],
                    'consented_on' => Carbon::now(),
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }

    /**
     * @param array $familyData
     * @param App\Centre $centre
     * @return void
     */
    public function createRegistrationTestCase($familyData, App\Centre $centre = null)
    {
        $carers = [];
        $children = [];
        $eligibilities = config('arc.reg_eligibilities');

        if (is_null($centre)) {
            $centre = factory(App\Centre::class)->create();
        }

        $family = factory(App\Family::class)->make();
        $family->lockToCentre($centre);
        $family->save();

        foreach ($familyData['carers'] as $carer) {
            $carers[] = factory(App\Carer::class)->make(['name' => $carer['name']]);
        }
        $family->carers()->saveMany($carers);

        foreach ($familyData['children'] as $child) {
            $children[] = factory(App\Child::class, $child['age'])->states($child['state'])->make();
        }

        $family->children()->saveMany($children);
        Log::info($family);
        return App\Registration::create(
            [
                'centre_id' => $centre->id,
                'family_id' => $family->id,
                'eligibility' => $eligibilities[mt_rand(0, count($eligibilities) - 1)],
                'consented_on' => $familyData['joined_on'],
            ]
        );
    }

}

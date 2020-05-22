<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\User;
use App\Voucher;

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

        $this->user = User::where('name', 'demoseeder')->first();
        if (!$this->user) {
            $this->user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        // set some variables.
        Auth::login($this->user);

        $this->centre = $this->user->centre;

        // centre without SK rules for fam1 & fam4 that is not in same area as families 3, 5 and 6
        $testCentreFam1And4 = App\Centre::find(7);
        $testCentreFam1And4->sponsor_id = App\Sponsor::find(5)->id;
        $testCentreFam1And4->save();
        // centre with SK rules for fam2 that is not in same area as families 3, 5 and 6

        $testCentreFam2 = App\Centre::find(2);
        $testCentreFam2->sponsor_id = App\Sponsor::find(2)->id;
        $testCentreFam2->save();

        // centre with SK rules for fam3 that is in a different area from all other families
        $testCentreFam3 = App\Centre::find(1);
        $testCentreFam3->sponsor_id = App\Sponsor::find(1)->id;
        $testCentreFam3->save();

        // centre with SK rules but without toggle for fam 5 that is in a different area from all other families

        $testCentreFam5 = App\Centre::find(3);
        $testCentreFam5->sponsor_id = App\Sponsor::find(3)->id;
        $testCentreFam5->save();

        // centre with non SK rules for fam 6 that is in a different area from all other families
        $testCentreFam6 = App\Centre::find(6);
        $testCentreFam6->sponsor_id = App\Sponsor::find(4)->id;
        $testCentreFam6->save();

        $familyData1 = [
            'carers' => [
                ['name' => '1MAY20a-VC2-CH1-HI-042019'],
                ['name' => 'MAY20b-VC2-CH1-HI-042019'],
            ],
            'children' => [
                ['age' => 'almostOne'],
            ],
            'joined_on' => Carbon::create(2019, 4, 9),
            'collection' => [
                ['date' => Carbon::create(2019, 4, 9)],
                ['date' => Carbon::create(2019, 4, 23)],
                ['date' => Carbon::create(2019, 5, 3)],
            ],
        ];

        $familyData2 = [
            'carers' => [
                ['name' => '2MAY20-VC1-CH2-HI-122018']
            ],
            'children' => [
                ['age' => 'underOne', 'state' => 'unverified'],
                ['age' => 'underSchoolAge', 'state' => 'unverified'],
                ['age' => 'overSchoolAge', 'state' => 'unverified'],
            ],
            'joined_on' => Carbon::create(2018, 12, 14),
            'collection' => [
                ['date' => Carbon::create(2018, 12, 14)],
                ['date' => Carbon::create(2019, 1, 15)],
                ['date' => Carbon::create(2019, 1, 29)],
                ['date' => Carbon::create(2019, 2, 5)],
                ['date' => Carbon::create(2019, 2, 26)],
                ['date' => Carbon::create(2019, 3, 19)],
                ['date' => Carbon::create(2019, 5, 14)],
                ['date' => Carbon::create(2019, 5, 28)],
                ['date' => Carbon::create(2019, 6, 11)],
                ['date' => Carbon::create(2019, 6, 18)],
                ['date' => Carbon::create(2019, 9, 17)],
                ['date' => Carbon::create(2019, 10, 22)],
                ['date' => Carbon::create(2019, 11, 2019)],
            ],
        ];

        $familyData3 = [
            'carers' => [
                ['name' => '3MAY20-VC1-CH6-HAS-112019'],
            ],
            'children' => [
                ['age' => 'underSchoolAge', 'state' => 'verified'],
                ['age' => 'underSchoolAge', 'state' => 'verified'],
                ['age' => 'readyForSchool', 'state' => 'unverified'],
                ['age' => 'overSchoolAge', 'state' => 'unverified'],
                ['age' => 'overSchoolAge', 'state' => 'verified'],
                ['age' => 'overSchoolAge', 'state' => 'verified'],
            ],
            'joined_on' => Carbon::create(2019, 11, 22),
            'collection' => [
                ['date' => Carbon::create(2019, 11, 29)],
                ['date' => Carbon::create(2019, 12, 6)],
                ['date' => Carbon::create(2019, 12, 13)],
                ['date' => Carbon::create(2020, 1, 3)],
                ['date' => Carbon::create(2020, 1, 10)],
                ['date' => Carbon::create(2020, 1, 16)],
                ['date' => Carbon::create(2020, 1, 23)],
                ['date' => Carbon::create(2020, 5, 12)],
            ]
        ];


        $familyData4 = [
            'carers' => [
                ['name' => '4MAY20-VC1-CH1P-HA-012020'],
            ],
            'children' => [
                ['age' => 'underSchoolAge'],
                ['age' => 'unbornChild'],
            ],
            'joined_on' => Carbon::create(2020, 1, 30),
            'collection' => [
                ['date' => Carbon::create(2020, 1, 30)],
                ['date' => Carbon::create(2020, 5, 15)],
            ]
        ];

        $familyData5 = [
            'carers' => [
                ['name' => '5MAY20-VC1-CH3-HA-112015'],
            ],
            'children' => [
                ['age' => 'overSchoolAge', 'state' => 'unverified'],
                ['age' => 'overSchoolAge', 'state' => 'unverified'],
                ['age' => 'underSchoolAge', 'state' => 'unverified'],
            ],
            'joined_on' => Carbon::create(2019, 11, 12),
            'collection' => [
                ['date' => Carbon::create(2019, 11, 12)],
                ['date' => Carbon::create(2019, 11, 27)],
                ['date' => Carbon::create(2019, 12, 3)],
                ['date' => Carbon::create(2019, 12, 16)],
                ['date' => Carbon::create(2019, 12, 20)],
                ['date' => Carbon::create(2020, 1, 7)],
                ['date' => Carbon::create(2020, 1, 20)],
                ['date' => Carbon::create(2020, 1, 24)],
                ['date' => Carbon::create(2020, 2, 5)],
                ['date' => Carbon::create(2020, 5, 22)],
            ]
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
            'collection' => [
                ['date' => Carbon::create(2020, 4, 1)],
                ['date' => Carbon::create(2020, 5, 1)],
            ]
        ];

        $this->createRegistrationTestCase($familyData1, $testCentreFam1And4);
        $this->createRegistrationTestCase($familyData2, $testCentreFam2);
        $this->createRegistrationTestCase($familyData3, $testCentreFam3);
        $this->createRegistrationTestCase($familyData4, $testCentreFam1And4);
        $this->createRegistrationTestCase($familyData5, $testCentreFam5);
        $this->createRegistrationTestCase($familyData6, $testCentreFam6);
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
            if (isset($child['state']) && !is_null($child['state'])) {
                $children[] = factory(App\Child::class, $child['age'])->states($child['state'])->make();
            } else {
                $children[] = factory(App\Child::class, $child['age'])->make();
            }
        }

        $family->children()->saveMany($children);

        $registration = App\Registration::create(
            [
                'centre_id' => $centre->id,
                'family_id' => $family->id,
                'eligibility' => $eligibilities[mt_rand(0, count($eligibilities) - 1)],
                'consented_on' => $familyData['joined_on'],
            ]
        );

        $pri_carer = $registration->family->carers->first();

        // Get/make the current bundle
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        foreach ($familyData['collection'] as $voucherCollectionDate) {
            // Create three random vouchers and transition to dispatched, then deliver the bundle
            /** @var Collection $vs */
            $vs1 = factory(Voucher::class, 'printed', 3)
                ->create()
                ->each(function (Voucher $v) {
                    $v->applyTransition('dispatch');
                });

            // Generate a corresponding delivery
            $delivery = factory(App\Delivery::class)->create([
                // Using the reg centre id but it won't match with voucher sponsor since they are random
                'centre_id' => $registration->centre->id,
                'dispatched_at' => $voucherCollectionDate,
                // These are random and will not be a proper range, but should be identifiable with this.
                'range' => $vs1[0]->code . '-' . $vs1[2]->code,
            ]);
            $delivery->vouchers()->saveMany($vs1);

            // Ask bundle to add these vouchers.
            $bundle->addVouchers($vs1->pluck('code')->toArray());

            // "Collect" it 14 days ago, by hand as the methods don't really exist, yet
            $bundle->disbursed_at = Carbon::now()->subDays(14);
            $bundle->disbursingCentre()->associate($registration->centre);
            $bundle->collectingCarer()->associate($pri_carer);
            $bundle->disbursingUser()->associate($this->user);
            $bundle->save();
        }

        // Again, the current bundle, should be blank as we just saved one.
        /** @var Bundle $bundle2 */
        $registration->currentBundle();
    }
}

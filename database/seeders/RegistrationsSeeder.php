<?php
namespace Database\Seeders;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Delivery;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        $this->createRegistrationForCentre(2, CentreUser::find(1)->centre);
        $this->createRegistrationForCentre(2, CentreUser::find(2)->centre);
        $this->createRegistrationForCentre(2, CentreUser::find(3)->centre);


        // Create some regs not randomly as we want to avoid the SP centres for now
		factory(Registration::class)->create(["centre_id" => 1]);
		factory(Registration::class)->create(["centre_id" => 2]);
		factory(Registration::class)->create(["centre_id" => 3]);
		factory(Registration::class)->create(["centre_id" => 4]);
		factory(Registration::class)->create(["centre_id" => 5]);
		factory(Registration::class)->create(["centre_id" => 6]);
		factory(Registration::class)->create(["centre_id" => 7]);
		factory(Registration::class)->create(["centre_id" => 8]);


        // One registration with our CC with an incative family.
        $inactive = factory(Registration::class)
            ->create();

        // We will have a better way of incorporating this into factories - but currenly families get created by Reg seeds.
        // So for now, this ensures we have one for testing.
        $family = $inactive->family;
        $family->leaving_on = Carbon::now()->subMonths(2);
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();

        // create 3 regs for a *new* centre (with no users)
        $this->createRegistrationForCentre(3000);
        $this->createRegistrationForScottishCentre(1);

		// create some SP regs
		$this->createRegistrationForSPCentre(2,  Centre::find(10));
		$this->createRegistrationForSPCentre(2,  Centre::find(11));

        // create a registration with a bundle
        $bundle = factory(Bundle::class)->create();
        factory(Registration::class)->create(["centre_id" => 13])->bundles()->save($bundle);

        $this->user = User::where('name', 'demoseeder')->first();
        if (!$this->user) {
            $this->user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        // set some variables.
        Auth::login($this->user);

        $this->centre = $this->user->centre;

        // create the test families asked for by Faith and Karl
        foreach ($this->familiesData() as $familyData) {
            $this->createRegistrationTestCase($familyData, $familyData['centre']);
        }
    }

    /**
     * This is horrible and there's a better way to make seeds, imagine.
     * @param $quantity
     * @param Centre $centre
     * @return Collection
     */
    public function createRegistrationForCentre($quantity, Centre $centre = null)
    {
        // set the loop and centre
        $quantity = ($quantity) ? $quantity : 1;
        if (is_null($centre)) {
            $centre = factory(Centre::class)->create();
        }

        $registrations = [];

        $eligibilities_hsbs = config('arc.reg_eligibilities_hsbs');
        $eligibilities_nrpf = config('arc.reg_eligibilities_nrpf');

		$spCentreIDs = ['9', '10', '11'];
        foreach (range(1, $quantity) as $q) {
			// We want to make sure regs are not assigned to the specific SP centres at random
			if (!array_key_exists($centre->id, $spCentreIDs)) {
				// create a family and set it up.
				$family = factory(Family::class)->make();
				$family->lockToCentre($centre);
				$family->save();
				$family->carers()->saveMany(factory(Carer::class, random_int(1, 3))->make());
				$family->children()->saveMany(factory(Child::class, random_int(0, 4))->make());
				$eligibility_hsbs = $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)];
				$eligible_from = null;
				if ($eligibility_hsbs === 'healthy-start-receiving') {
					$eligible_from = Carbon::now();
				}

				$registrations[] = Registration::create(
					[
						'centre_id' => $centre->id,
						'family_id' => $family->id,
						'eligibility_hsbs' => $eligibility_hsbs,
						'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
						'consented_on' => Carbon::now(),
						'eligible_from' => $eligible_from
					]
				);
			}
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }

    /**
     * @param $quantity
     * @param Centre $centre
     * @return Collection
     */
    public function createRegistrationForScottishCentre($quantity, Centre $centre = null)
    {
        // set the loop and centre
        $quantity = ($quantity) ? $quantity : 1;
        if (is_null($centre)) {
            $centre = factory(Centre::class)->create([
              'sponsor_id' => 8
            ]);
        }
        $user = CentreUser::where('name', 'ARC CC User')->first();
        if (!$user) {
            $user = factory(CentreUser::class)->create(['name' => 'ARC CC User']);
        };
        $user->centres()->attach([
            $centre->id => ['homeCentre' => false]
        ]);

        $registrations = [];

        $eligibilities_hsbs = config('arc.reg_eligibilities_hsbs');
        $eligibilities_nrpf = config('arc.reg_eligibilities_nrpf');

        foreach (range(1, $quantity) as $q) {
            // create a family and set it up.
            $family = factory(Family::class)->make();
            $family->lockToCentre($centre);
            $family->save();
            $family->carers()->saveMany(factory(Carer::class, random_int(1, 3))->make());
            $family->children()->save(factory(Child::class)->state('readyForPrimarySchool')->make());
            $family->children()->save(factory(Child::class)->state('canDefer')->make());
            $family->children()->save(factory(Child::class)->state('canNotDefer')->make());
            $eligibility_hsbs = $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)];
            $eligible_from = null;
            if ($eligibility_hsbs === 'healthy-start-receiving') {
              $eligible_from = Carbon::now();
            }

            $registrations[] = Registration::create(
                [
                    'centre_id' => $centre->id,
                    'family_id' => $family->id,
                    'eligibility_hsbs' => $eligibility_hsbs,
                    'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
                    'consented_on' => Carbon::now(),
                    'eligible_from' => $eligible_from
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }

	/**
	 * @param $quantity
	 * @param Centre $centre
	 * @return Collection
	 */
	public function createRegistrationForSPCentre($quantity, Centre $centre = null)
	{
		// set the loop and centre
		$quantity = ($quantity) ? $quantity : 1;

		$registrations = [];

		foreach (range(1, $quantity) as $q) {
			// create a family and set it up.
			$family = factory(Family::class)->make();
			$family->lockToCentre($centre);
			$family->save();
			$family->carers()->saveMany(factory(Carer::class, random_int(1, 3))->make());
			// Make the first carer a "child" as is the SP way
			$family->carers()->save(factory(Child::class)->create([
				'dob' => '1983-01-01',
				'family_id' => $family->id,
				'born' => 1
			]));
			$family->children()->save(factory(Child::class)->state('readyForPrimarySchool')->make());
			$family->children()->save(factory(Child::class)->state('canDefer')->make());
			$family->children()->save(factory(Child::class)->state('canNotDefer')->make());


			$registrations[] = Registration::create(
				[
					'centre_id' => $centre->id,
					'family_id' => $family->id,
					'eligibility_hsbs' => null,
					'eligibility_nrpf' => null,
					'consented_on' => Carbon::now(),
					'eligible_from' => Carbon::now()
				]
			);
		}
		// Return the collection in case anyone needs it.
		return collect($registrations);
	}


	/**
     * @param array $familyData
     * @param Centre $centre
     * @return void
     */
    public function createRegistrationTestCase($familyData, Centre $centre = null)
    {
        $carers = [];
        $children = [];
        $eligibilities_hsbs = config('arc.reg_eligibilities_hsbs');
        $eligibilities_nrpf = config('arc.reg_eligibilities_nrpf');

        if (is_null($centre)) {
            $centre = factory(Centre::class)->create();
        }
		$spCentreIDs = ['9', '10', '11'];
		if (!array_key_exists($centre->id, $spCentreIDs)) {
			$family = factory(Family::class)->make();
			$family->lockToCentre($centre);
			$family->save();
		}

        foreach ($familyData['carers'] as $carer) {
            $carers[] = factory(Carer::class)->make(['name' => $carer['name']]);
        }
        $family->carers()->saveMany($carers);

        foreach ($familyData['children'] as $child) {
            if (isset($child['state']) && !is_null($child['state'])) {
                $children[] = factory(Child::class, $child['age'])->states($child['state'])->make();
            } else {
                $children[] = factory(Child::class, $child['age'])->make();
            }
        }

        $family->children()->saveMany($children);

        $registration = Registration::create(
            [
                'centre_id' => $centre->id,
                'family_id' => $family->id,
                'eligibility_hsbs' => $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)],
                'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
                'consented_on' => $familyData['joined_on'],
            ]
        );

        $pri_carer = $registration->family->carers->first();

        foreach ($familyData['collection'] as $voucherCollection) {

            // Get/make the current bundle
            /** @var Bundle $bundle */
            $bundle = $registration->currentBundle();
            // Create three random vouchers and transition to dispatched, then deliver the bundle
            /** @var Collection $vs */
            $vs1 = factory(Voucher::class, 3)->state('printed')
                ->create()
                ->each(function (Voucher $v) {
                    $v->applyTransition('dispatch');
                });

            // Generate a corresponding delivery
            $delivery = factory(Delivery::class)->create([
                // Using the reg centre id but it won't match with voucher sponsor since they are random
                'centre_id' => $registration->centre->id,
                'dispatched_at' => $voucherCollection['date']->copy()->subDays(10),
                // These are random and will not be a proper range, but should be identifiable with this.
                'range' => $vs1[0]->code . '-' . $vs1[2]->code,
            ]);

            $delivery->vouchers()->saveMany($vs1);

            // Ask bundle to add these vouchers.
            $bundle->addVouchers($vs1->pluck('code')->toArray());

            // "Collect" it on collection date, by hand as the methods don't really exist, yet
            $bundle->disbursed_at = $voucherCollection['date'];
            $bundle->disbursingCentre()->associate($registration->centre);
            $bundle->collectingCarer()->associate($pri_carer);
            $bundle->disbursingUser()->associate($this->user);
            $bundle->save();

            // Again, the current bundle, should be blank as we just saved one.
            /** @var Bundle $bundle2 */
            $registration->currentBundle();
        }
        return $registration;
    }

    /**
     * @return array
     */
    private function familiesData()
    {
        return [
            'familyData1' => [
                // centre without SK rules that is not in same area as families 3, 5 and 6
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(5)->id]),
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
            ],
            'familyData2' => [
                // centre with SK rules that is not in same area as families 3, 5 and 6
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(2)->id]),
                'carers' => [
                    ['name' => '2MAY20-VC1-CH2-HI-122018']
                ],
                'children' => [
                    ['age' => 'underOne', 'state' => 'unverified'],
                    ['age' => 'betweenOneAndPrimarySchoolAge', 'state' => 'unverified'],
                    ['age' => 'isPrimarySchoolAge', 'state' => 'unverified'],
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
                    ['date' => Carbon::create(2019, 11, 18)],
                ],
            ],
            'familyData3' => [
                // centre with SK rules that is in a different area from all other families
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(1)->id]),
                'carers' => [
                    ['name' => '3MAY20-VC1-CH6-HAS-112019'],
                ],
                'children' => [
                    ['age' => 'betweenOneAndPrimarySchoolAge', 'state' => 'verified'],
                    ['age' => 'betweenOneAndPrimarySchoolAge', 'state' => 'verified'],
                    ['age' => 'readyForPrimarySchool', 'state' => 'unverified'],
                    ['age' => 'isPrimarySchoolAge', 'state' => 'unverified'],
                    ['age' => 'isPrimarySchoolAge', 'state' => 'verified'],
                    ['age' => 'isPrimarySchoolAge', 'state' => 'verified'],
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
                ],
            ],

            'familyData4' => [
                // centre without SK rules that is not in same area as families 3, 5 and 6
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(5)->id]),
                'carers' => [
                    ['name' => '4MAY20-VC1-CH1P-HA-012020'],
                ],
                'children' => [
                    ['age' => 'betweenOneAndPrimarySchoolAge'],
                    ['age' => 'unbornChild'],
                ],
                'joined_on' => Carbon::create(2020, 1, 30),
                'collection' => [
                    ['date' => Carbon::create(2020, 1, 30)],
                    ['date' => Carbon::create(2020, 5, 15)],
                ],
            ],
            'familyData5' => [
                // centre with SK rules but without toggle that is in a different area from all other families
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(3)->id]),
                'carers' => [
                    ['name' => '5MAY20-VC1-CH3-HA-112015'],
                ],
                'children' => [
                    ['age' => 'isPrimarySchoolAge', 'state' => 'unverified'],
                    ['age' => 'isPrimarySchoolAge', 'state' => 'unverified'],
                    ['age' => 'betweenOneAndPrimarySchoolAge', 'state' => 'unverified'],
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
                ],
            ],
            'familyData6' => [
                // centre with non SK rules that is in a different area from all other families
                'centre' => factory(Centre::class)->create(['sponsor_id' => Sponsor::find(4)->id]),
                'carers' => [
                    ['name' => '6MAY-VC1-CH2-HI-032020'],
                ],
                'children' => [
                    ['age' => 'underOne', 'state' => 'unverified'],
                    ['age' => 'betweenOneAndPrimarySchoolAge', 'state' => 'verified'],
                ],
                'joined_on' => Carbon::create(2020, 4, 1),
                'collection' => [
                    ['date' => Carbon::create(2020, 4, 1)],
                    ['date' => Carbon::create(2020, 5, 1)],
                ],
            ],
        ];
    }
}

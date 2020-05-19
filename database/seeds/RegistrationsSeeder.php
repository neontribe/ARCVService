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

        $this->createRegistrationTestCases();
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

    public function createRegistrationTestCases()
    {
        $eligibilities = config('arc.reg_eligibilities');

        $family = factory(App\Family::class)->make();
        $family->lockToCentre(App\Centre::find(1)); //to be confirmed
        dd($family);
        $family->save();
        factory(App\Carer::class)->create([
            'name' => 'MAY20-VC1-CH6-HAS-112019',
            'family_id' => $family->id,
        ]);

        $child1 = factory(App\Child::class, 'almostOne')->states('verified')->make();
        $child2 = factory(App\Child::class, 'underSchoolAge')->states('verified')->make();
        $child3 = factory(App\Child::class, 'readyForSecondarySchool')->states('unverified')->make();
        $child4 = factory(App\Child::class, 'readyForSecondarySchool')->states('unverified')->make();
        $child5 = factory(App\Child::class, 'readyForSecondarySchool')->states('verified')->make();
        $child6 = factory(App\Child::class, 'readyForSecondarySchool')->states('verified')->make();

        $children = collect($child1, $child2, $child3, $child4, $child5, $child6);
        $family->children()->saveMany($children);

        $registration = App\Registration::create(
            [
                'centre_id' => App\Centre::find(1)->id,
                'family_id' => $family->id,
                'eligibility' => $eligibilities[mt_rand(0, count($eligibilities) - 1)],
                'consented_on' => Carbon::create(2019, 11, 22),
            ]
        );

        return collect($registration);
    }
}

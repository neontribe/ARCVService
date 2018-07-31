<?php

use Illuminate\Database\Seeder;

class UsabilityScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Centre 1
        $centre1 = factory(App\Centre::class)->create([
            'name' => 'FM Centre',
            'prefix' => 'FMAT',
            'print_pref' => 'collection',
        ]);

        // Create Centre 2
        // - that has collection
        $centre2 = factory(App\Centre::class)->create([
            'name' => 'Collection Centre',
            'prefix' => 'COLL',
            'print_pref' => 'collection',

        ]);

        // Create Centre 3
        // - that has individual
        $centre3 = factory(App\Centre::class)->create([
            'name' => 'Individual Centre',
            'prefix' => 'INDY',
            'print_pref' => 'individual',
        ]);

        factory(App\CentreUser::class)->create([
            "name"  => "ARC FM User",
            "email" => "arc+fm@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => $centre1->id,
            "role" => "foodmatters_user",
        ]);

        factory(App\CentreUser::class)->create([
            "name"  => "ARC Collecting User",
            "email" => "arc+coll@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => $centre2->id,
            "role" => "centre_user",
        ]);

        factory(App\CentreUser::class)->create([
            "name"  => "ARC Indy User",
            "email" => "arc+indy@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => $centre3->id,
            "role" => "centre_user",
        ]);

        // Create 3 families in centre2
        $centre2_families = $this->createRegistrationForCentre(3, $centre2);

        // Add an almostOne child to them
        foreach ($centre2_families as $family) {
            factory(App\Child::class, 'almostOne')->create([
                'family_id' => $family->id
            ]);
        }

        // Create 3 families in centre2
        $centre3_families = $this->createRegistrationForCentre(3, $centre3);

        // Add an almostOne child to them
        foreach ($centre3_families as $family) {
            factory(App\Child::class, 'almostOne')->create([
                'family_id' => $family->id
            ]);
        }

        // make 10 registrations in both centres 2 and 3.
        $this->createRegistrationForCentre(10, $centre2);
        $this->createRegistrationForCentre(10, $centre3);
    }

    /**
     * This is horrible and there's a better way to mke seeds, imagine.
     * @param $quantity
     * @param App\Centre $centre
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

        $eligibilities = ['healthy-start', 'other'];

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
                    'consented_on' => Carbon\Carbon::now(),
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }
}

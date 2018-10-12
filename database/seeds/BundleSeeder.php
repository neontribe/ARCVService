<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // get or create the seeder user
        $user = App\User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(App\User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        $registration = $this->createRegistrationForCentre(1, Auth::user()->centre)->first();
        $carers = $registration->family->carers->all();

        $pri_carer = array_shift($carers);
        $pri_carer->name = "Bobby Bundle";
        $pri_carer->save();

        $bundle = $registration->currentBundle();

        // Create three vouchers and transition to printed.
        $vs = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function ($v) use ($bundle) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->bundle()->associate($bundle);
                $v->save();
            });
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
                    'consented_on' => Carbon::now(),
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }

}

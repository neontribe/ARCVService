<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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

        $centre = Auth::user()->centre;

        $registration = $this->createRegistrationForCentre(1, $centre)->first();
        $carers = $registration->family->carers->all();

        $pri_carer = array_shift($carers);
        $pri_carer->name = "Bobby Bundle";
        $pri_carer->save();

        // Get, actually make as it should be blank, the current bundle.
        /** @var App\Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Create three random vouchers and transition to printed, then bundle
        /** @var Collection $vs */
        $vs1 = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function (\App\Voucher $v) {
                $v->applyTransition('order');
               
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
            });

        // Ask bundle to add these vouchers.
        $bundle->addVouchers($vs1->pluck('code')->toArray());

        // "Collect" it 14 days ago, by hand as the methods don't really exist, yet
        $bundle->disbursed_at = Carbon::now()->subDays(14);
        $bundle->disbursingCentre()->associate($registration->centre);
        $bundle->collectingCarer()->associate($pri_carer);
        $bundle->disbursingUser()->associate(Auth::user());
        $bundle->save();

        // Again, the current bundle, should be blank as we just saved one.
        /** @var App\Bundle $bundle2 */
        $registration->currentBundle();
    }

    /**
     * This is a seeder specific version of this function (see children)
     *
     * @param $quantity
     * @param App\Centre $centre
     * @return Collection
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
            $family->children()->save(factory(App\Child::class, 'underSchoolAge')->make());

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

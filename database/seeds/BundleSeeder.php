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
        /** @var \Illuminate\Support\Collection $vs */
        $vs1 = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function (\App\Voucher $v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
            });

        // Ask bundle to add these vouchers.
        $bundle->addVouchers($vs1->pluck('code')->toArray());

        // "Collect" it last week, by hand as the methods don't really exist, yet
        $bundle->disbursed_at = Carbon::now()->subDays(7);
        $bundle->save();

        // Again, the current bundle, should be blank as we just saved one.
        /** @var App\Bundle $bundle2 */
        $bundle2 = $registration->currentBundle();

        /** @var \Illuminate\Support\Collection $vs2 */
        $vs2 = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function (\App\Voucher $v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
            });

        $bundle2->addVouchers($vs2->pluck('code')->toArray());
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
                    'consented_on' => Carbon::now(),
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }

}

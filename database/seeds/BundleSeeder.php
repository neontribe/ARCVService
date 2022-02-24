<?php

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Child;
use App\Family;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;


class BundleSeeder extends Seeder
{
    /** @var User $user */
    private $user;
    /** @var Centre $centre */
    private $centre;

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        // get or create the seeder user
        $this->user = User::where('name', 'demoseeder')->first();
        if (!$this->user) {
            $this->user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        // set some variables.
        Auth::login($this->user);

        $this->centre = $this->user->centre;

        // TODO: make these in a better way.
        $this->createFamilyWithDisbursedVouchers();
        $this->createFamilyWithWaitingVouchers();
    }

    /**
     * Creates Bobby Bundle with the disbursed vouchers.
     */
    public function createFamilyWithDisbursedVouchers()
    {
        $registration = $this->createRegistrationForCentre(1, $this->centre)->first();
        $carers = $registration->family->carers->all();

        $pri_carer = array_shift($carers);
        $pri_carer->name = "Bobby Bundle";
        $pri_carer->save();

        // Get/make the current bundle
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Create three random vouchers and transition to dispatched, then deliver and bundle.
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
            'dispatched_at' => Carbon::now(),
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

        // Again, the current bundle, should be blank as we just saved one.
        /** @var Bundle $bundle2 */
        $registration->currentBundle();
    }

    /**
     * Creates Wendy Waiting with the uncollected vouchers.
     */
    public function createFamilyWithWaitingVouchers()
    {
        $registration = $this->createRegistrationForCentre(1, $this->centre)->first();
        $carers = $registration->family->carers->all();

        $pri_carer = array_shift($carers);
        $pri_carer->name = "Wendy Waiting";
        $pri_carer->save();

        // Get/make the current bundle
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Create three random vouchers and transition to dispatched, then deliver then bundle
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
            'dispatched_at' => Carbon::now(),
            // These are random and will not be a proper range, but should be identifiable with this.
            'range' => $vs1[0]->code . '-' . $vs1[2]->code,
        ]);
        $delivery->vouchers()->saveMany($vs1);

        // Ask bundle to add these vouchers.
        $bundle->addVouchers($vs1->pluck('code')->toArray());
    }


    /**
     * This is a seeder specific version of this function (see children)
     *
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

        foreach (range(1, $quantity) as $q) {
            try {
                // `random_int()` throws an Exception when insufficient entropy.
                // Who knew!
                $numCarers = random_int(1, 3);
            } catch (Exception $e) {
                // In this case, just set it to 2 and be done.
                $numCarers = 2;
            }
            // Create a family and set it up.
            $family = factory(Family::class)->make();
            $family->lockToCentre($centre);
            $family->save();
            $family->carers()->saveMany(factory(Carer::class, $numCarers)->make());
            $family->children()->save(factory(Child::class, 'betweenOneAndPrimarySchoolAge')->make());

            $registrations[] = App\Registration::create(
                [
                    'centre_id' => $centre->id,
                    'family_id' => $family->id,
                    'eligibility_hsbs' => $eligibilities_hsbs[mt_rand(0, count($eligibilities_hsbs) - 1)],
                    'eligibility_nrpf' => $eligibilities_nrpf[mt_rand(0, count($eligibilities_nrpf) - 1)],
                    'consented_on' => Carbon::now(),
                ]
            );
        }
        // Return the collection in case anyone needs it.
        return collect($registrations);
    }
}

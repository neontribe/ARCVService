<?php
namespace Database\Seeders;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class TestActiveUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Set today
        $today = Carbon::today()->startOfDay();

        // Get or create the seeder user
        $user = User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        // Make a centre for them
        /** @var Centre $c */
        $c = factory(Centre::class)->create([
            "sponsor_id" => 1,
            "name" => "Active User Test"
        ]);

        // ARC CCUSer who can download.
        $user = factory(CentreUser::class)->state('withDownloader')->create([
            "name"  => "ARC CCDownloader",
            "email" => "arc+ccdownloader@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
        ]);
        // Attach to our centre
        $user->centres()->attach($c->id, ['homeCentre' => true]);

        // Setup the no pickup regs
        $noPickupRegData = [
            ["name" => "inactive-no-pickup", "join" => $today->copy()->subWeeks(12)],
            ["name" => "active-no-pickup-3-week", "join" => $today->copy()->subWeeks(3)],
            ["name" => "active-no-pickup-2-week", "join" => $today->copy()->subWeeks(2)],
            ["name" => "active-no-pickup-1-week", "join" => $today->copy()->subWeeks(1)],
        ];

        // Make the no pickup regs
        factory(Registration::class, count($noPickupRegData))
            ->create()
            ->each(function (Registration $reg, $key) use ($c, $noPickupRegData) {
                $data = $noPickupRegData[$key];

                // Set Family name
                $carer = $reg->family->carers->first();
                $carer->name = $data["name"];
                $carer->save();

                // Force Centre switch on Family
                $reg->family->lockToCentre($c, true);
                // Change Centre on Registration
                $reg->centre_id = $c->id;
                // Update the created at date
                $reg->created_at = $data["join"];
                // Save it
                $reg->save();
            });

        // Setup the pickup regs
        $pickupRegData = [
            ["name" => "active-pickup-1-week-A", "bundle" => $today->copy()->subWeeks(1)],
            ["name" => "active-pickup-1-week-B", "bundle" => $today->copy()->subWeeks(1)],
            ["name" => "active-pickup-1-week-C", "bundle" => $today->copy()->subWeeks(1)],
            ["name" => "active-pickup-2-week", "bundle" => $today->copy()->subWeeks(2)],
            ["name" => "active-pickup-3-week", "bundle" => $today->copy()->subWeeks(3)],
            ["name" => "inactive-pickup-5-week", "bundle" => $today->copy()->subWeeks(5)],
        ];

        factory(Registration::class, count($pickupRegData))
            ->create()
            ->each(function (Registration $reg, $key) use ($c, $pickupRegData) {
                $data = $pickupRegData[$key];

                // Set Family name
                /** @var Carer $carer */
                $carer = $reg->family->carers->first();
                $carer->name = $data["name"];
                $carer->save();

                // Force Centre switch on Family
                $reg->family->lockToCentre($c, true);
                // Change Centre on Registration
                $reg->centre_id = $c->id;

                // Update the created at date
                $reg->created_at = $data["bundle"];

                // Save it
                $reg->save();
                $reg = $reg->fresh();

                // Make a bundle
                /** @var Bundle $bundle */
                $bundle = $reg->currentBundle();

                // Create three random vouchers and transition to dispatched, then bundle
                /** @var Collection $vs */
                $vs = factory(Voucher::class, 3)->state('printed')
                    ->create()
                    ->each(function (Voucher $v) {
                        $v->applyTransition('dispatch');
                    });

                // Ask bundle to add these vouchers.
                $bundle->addVouchers($vs->pluck('code')->toArray());

                // "Collect" it
                $bundle->disbursed_at = $data["bundle"];
                $bundle->disbursingCentre()->associate($c);
                $bundle->collectingCarer()->associate($carer);
                $bundle->disbursingUser()->associate(Auth::user());
                $bundle->save();

                // Again, reset the current bundle, should be blank as we just saved one.
                $reg->currentBundle();
            });
    }
}

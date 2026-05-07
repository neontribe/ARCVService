<?php

namespace Tests\Unit\Models;

use App\Bundle;
use App\Centre;
use App\CentreUser;
use App\Delivery;
use App\Registration;
use App\Sponsor;
use App\Voucher;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

class CentreModelTest extends TestCase
{
    use RefreshDatabase;

    public function testItHasExpectedAttributes(): void
    {
        $centre = factory(Centre::class)->make();
        $this->assertNotNull($centre->name);
        $this->assertNotNull($centre->sponsor_id);
        $this->assertContains($centre->print_pref, config('arc.print_preferences'));
        $this->assertFalse($centre->can_collect);
    }

    public function testItHasASponsor(): void
    {
        $centre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);
        $this->assertInstanceOf(Sponsor::class, $centre->sponsor);
    }

    public function testItCanHaveRegistrations(): void
    {
        $centre = factory(Centre::class)->create();
        factory(Registration::class, 3)->create([
            'centre_id' => $centre->id,
        ]);
        $registrations = $centre->registrations;
        $this->assertInstanceOf(Collection::class, $registrations);
        $this->assertInstanceOf(Registration::class, $registrations[0]);
    }

    public function testItCanHaveNoRegistrations(): void
    {
        $centre = factory(Centre::class)->create();
        $registrations = $centre->registrations;
        $this->assertInstanceOf(Collection::class, $registrations);
        $this->assertEquals(0, $registrations->count());
    }

    public function testItCanHaveUsers(): void
    {
        $centre = factory(Centre::class)->create();

        factory(CentreUser::class, 3)
            ->create()
            ->each(
                function (CentreUser $centreUser) use ($centre) {
                    // Technically we should be setting one homeCentre for each user.
                    // Deemed unneccessary for purposes of this test at time of writing.
                    $centreUser->centres()->attach($centre);
                }
            );

        $centreUsers = $centre->centreUsers;
        $this->assertInstanceOf(Collection::class, $centreUsers);
        $this->assertInstanceOf(CentreUser::class, $centreUsers[0]);
    }

    public function testItCanHaveNeighbours(): void
    {
        $sponsor_a = factory(Sponsor::class)->create();
        $sponsor_b = factory(Sponsor::class)->create();

        $a_centres = factory(Centre::class, 2)->create([
            'sponsor_id' => $sponsor_a->id,
        ]);
        $b_centres = factory(Centre::class, 3)->create([
            'sponsor_id' => $sponsor_b->id,
        ]);

        // The ids of centre collections a and b are not the same.
        $this->assertNotEquals($a_centres->pluck('id'), $b_centres->pluck('id'));

        // For each centre in both collections, they know thine own neighbours...
        foreach ($a_centres as $ac) {
            $this->assertCount(2, $ac->neighbours);
            $this->assertEquals($a_centres->pluck('id'), $ac->neighbours->pluck('id'));
        }
        foreach ($b_centres as $bc) {
            $this->assertCount(3, $bc->neighbours);
            $this->assertEquals($b_centres->pluck('id'), $bc->neighbours->pluck('id'));
        }
    }

    public function testItCanBeSetToCollect(): void
    {
        $centre = factory(Centre::class)->states('collecting')->create();
        $this->assertTrue($centre->can_collect);
    }

    public function testCanCollectIsPersisted(): void
    {
        $centre = factory(Centre::class)->states('collecting')->create();
        $this->assertTrue(Centre::find($centre->id)->can_collect);
    }

    public function testCanCollectCanBeToggledToFalse(): void
    {
        $centre = factory(Centre::class)->states('collecting')->create();
        $centre->can_collect = false;
        $centre->save();
        $this->assertFalse(Centre::find($centre->id)->can_collect);
    }

    public function testMarketsIsAHasManyRelation(): void
    {
        $centre = factory(Centre::class)->create();
        $this->assertInstanceOf(HasMany::class, $centre->markets());
    }

    public function testMarketsOnNonCollectingCentreIsEmpty(): void
    {
        $centre = factory(Centre::class)->create();
        $this->assertCount(0, $centre->markets);
    }

    // --- availableVouchers ---

    public function testAvailableVouchersIsAHasManyThroughRelation(): void
    {
        $centre = factory(Centre::class)->create();
        $this->assertInstanceOf(HasManyThrough::class, $centre->availableVouchers());
    }

    public function testAvailableVouchersIsEmptyWithNoDeliveries(): void
    {
        $centre = factory(Centre::class)->create();
        $this->assertCount(0, $centre->availableVouchers()->get());
    }

    public function testAvailableVouchersOnlyIncludesDispatchedVouchers(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);

        factory(Voucher::class, 3)->state('dispatched')->create(['delivery_id' => $delivery->id]);
        // A recorded voucher has moved past dispatched — should be excluded.
        factory(Voucher::class)->state('recorded')->create(['delivery_id' => $delivery->id]);

        $this->assertCount(3, $centre->availableVouchers()->get());
    }

    public function testAvailableVouchersExcludesBundledVouchers(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);

        factory(Voucher::class, 2)->state('dispatched')->create(['delivery_id' => $delivery->id]);
        // A dispatched voucher already assigned to a bundle should be excluded.
        factory(Voucher::class)->state('dispatched')->create([
            'delivery_id' => $delivery->id,
            'bundle_id' => factory(Bundle::class)->create()->id,
        ]);

        $this->assertCount(2, $centre->availableVouchers()->get());
    }

    public function testAvailableVouchersDoesNotLeakAcrossCentres(): void
    {
        $centreA = factory(Centre::class)->create();
        $centreB = factory(Centre::class)->create();

        factory(Voucher::class, 3)->state('dispatched')->create([
            'delivery_id' => factory(Delivery::class)->create(['centre_id' => $centreA->id])->id,
        ]);
        factory(Voucher::class, 2)->state('dispatched')->create([
            'delivery_id' => factory(Delivery::class)->create(['centre_id' => $centreB->id])->id,
        ]);

        $this->assertCount(3, $centreA->availableVouchers()->get());
        $this->assertCount(2, $centreB->availableVouchers()->get());
    }

    // --- getPoolSize ---

    public function testGetPoolSizeIsZeroWithNoDeliveries(): void
    {
        $centre = factory(Centre::class)->create();
        $this->assertEquals(0, $centre->getPoolSize());
    }

    public function testGetPoolSizeReflectsAvailableVoucherCount(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);

        factory(Voucher::class, 4)->state('dispatched')->create(['delivery_id' => $delivery->id]);

        $this->assertEquals(4, $centre->getPoolSize());
    }

    public function testGetPoolSizeDoesNotCountIneligibleVouchers(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);

        factory(Voucher::class, 2)->state('dispatched')->create(['delivery_id' => $delivery->id]);
        // A recorded voucher has moved past dispatched — should not count toward the pool.
        factory(Voucher::class)->state('recorded')->create(['delivery_id' => $delivery->id]);

        $this->assertEquals(2, $centre->getPoolSize());
    }

    // --- claimFromPool ---

    public function testClaimFromPoolReturnsTheRequestedNumberOfVouchers(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);
        factory(Voucher::class, 5)->state('dispatched')->create(['delivery_id' => $delivery->id]);

        $claimed = $centre->claimFromPool(3);

        $this->assertInstanceOf(Collection::class, $claimed);
        $this->assertCount(3, $claimed);
        $this->assertInstanceOf(Voucher::class, $claimed->first());
    }

    public function testClaimFromPoolThrowsWhenPoolHasInsufficientVouchers(): void
    {
        $centre = factory(Centre::class)->create();
        $delivery = factory(Delivery::class)->create(['centre_id' => $centre->id]);
        factory(Voucher::class, 2)->state('dispatched')->create(['delivery_id' => $delivery->id]);

        $this->expectException(RuntimeException::class);
        $centre->claimFromPool(5);
    }

    public function testClaimFromPoolThrowsWhenPoolIsEmpty(): void
    {
        $centre = factory(Centre::class)->create();

        $this->expectException(RuntimeException::class);
        $centre->claimFromPool(1);
    }

    public function testClaimFromPoolDrawsFromOldestDeliveryFirst(): void
    {
        $centre = factory(Centre::class)->create();

        $olderDelivery = factory(Delivery::class)->create([
            'centre_id' => $centre->id,
            'dispatched_at' => now()->subMonth(),
        ]);
        $newerDelivery = factory(Delivery::class)->create([
            'centre_id' => $centre->id,
            'dispatched_at' => now(),
        ]);

        $olderVouchers = factory(Voucher::class, 2)->state('dispatched')->create([
            'delivery_id' => $olderDelivery->id,
        ]);
        factory(Voucher::class, 2)->state('dispatched')->create([
            'delivery_id' => $newerDelivery->id,
        ]);

        $claimed = $centre->claimFromPool(2);
        $claimedIds = $claimed->pluck('id');

        $this->assertTrue($claimedIds->contains($olderVouchers[0]->id));
        $this->assertTrue($claimedIds->contains($olderVouchers[1]->id));
    }

    public function testClaimFromPoolDoesNotReturnVouchersFromOtherCentres(): void
    {
        $centreA = factory(Centre::class)->create();
        $centreB = factory(Centre::class)->create();

        factory(Voucher::class, 3)->state('dispatched')->create([
            'delivery_id' => factory(Delivery::class)->create(['centre_id' => $centreA->id])->id,
        ]);
        $centreB_vouchers = factory(Voucher::class, 3)->state('dispatched')->create([
            'delivery_id' => factory(Delivery::class)->create(['centre_id' => $centreB->id])->id,
        ]);

        $claimed = $centreA->claimFromPool(3);

        $centreB_ids = $centreB_vouchers->pluck('id');
        $claimed->each(function ($v) use ($centreB_ids) {
            return $this->assertFalse($centreB_ids->contains($v->id));
        });
    }
}

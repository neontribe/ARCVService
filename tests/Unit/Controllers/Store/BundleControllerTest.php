<?php

namespace Tests\Unit\Controllers\Store;

use App\Bundle;
use App\Centre;
use App\CentreUser;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\Trader;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\StoreTestCase;

class BundleControllerTest extends StoreTestCase
{
    use RefreshDatabase;

    protected Centre $centre;
    protected CentreUser $centreUser;
    protected Registration $registration;
    protected array $testCodes;
    protected string $programme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        $this->centreUser = factory(CentreUser::class)->create([
            'name' => 'test user',
            'email' => 'testuser@example.com',
            'password' => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->registration = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
        ]);

        $this->testCodes = ['TST09999', 'TST10000', 'TST10001'];

        // Auth context is required by the voucher state-machine transition logger.
        Auth::login($this->centreUser);

        foreach ($this->testCodes as $code) {
            $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
            $voucher->applyTransition('dispatch');
        }

        $this->programme = Auth::user()->centre->sponsor->programme;

        Auth::logout();
    }

    // -------------------------------------------------------------------------
    // Private test helpers
    // -------------------------------------------------------------------------

    /**
     * Create a dispatched voucher and attach it directly to the given bundle,
     * bypassing alterVouchers so we can reach states that the normal flow would reject.
     */
    private function attachDispatchedVoucherToBundle(string $code, Bundle $bundle): Voucher
    {
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
        $voucher->applyTransition('dispatch');
        $voucher->bundle()->associate($bundle)->save();

        return $voucher;
    }

    /**
     * Valid disbursal payload using fixtures from setUp.
     */
    private function defaultDisbursalData(): array
    {
        return [
            'collected_at' => $this->centre->id,
            'collected_on' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'collected_by' => $this->registration->family->carers->first()->id,
        ];
    }

    /**
     * Named route to the voucher manager for the primary test registration.
     */
    private function managerRoute(?Registration $registration = null): string
    {
        return route('store.registration.voucher-manager', [
            'registration' => ($registration ?? $this->registration)->id,
        ]);
    }

    /**
     * Search session error messages for one matching the given regular expression.
     *
     * @param array<int, string|HtmlString> $errorMessages
     */
    private function hasMatchingErrorMessage(array $errorMessages, string $regex): bool
    {
        foreach ($errorMessages as $error) {
            if (preg_match($regex, (string) $error)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // create() — voucher manager view
    // =========================================================================

    public function testCreateRendersTheVoucherManagerView(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit($this->managerRoute())
            ->assertResponseStatus(200)
            ->seePageIs($this->managerRoute());
    }

    public function testCreateViewContainsRegistrationAndCarerData(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit($this->managerRoute())
            ->assertResponseStatus(200)
            ->see($this->centreUser->centre->name);
    }

    // =========================================================================
    // addVouchersToCurrentBundle() — single code / range
    // =========================================================================

    public function testICanAddSingleVouchers(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);

        $this->assertSame(1, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testICanAddManyVouchers(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, [
                'start' => $this->testCodes[0],
                'end' => $this->testCodes[count($this->testCodes) - 1],
            ]);

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);

        $this->assertSame(count($this->testCodes), $this->registration->currentBundle()->vouchers()->count());
    }

    public function testAddingVouchersRedirectsToManagerRouteOnSuccess(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);
    }

    public function testAddingVouchersFlashesGenericSuccessMessage(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        $response->seeInSession('message');
        $this->assertSame('Vouchers updated', Session::get('message'));
    }

    public function testICannotAddTooManyVouchersToABundle(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);
        $overMaxAdd = config('arc.bundle_max_voucher_append') + 1;
        $startCode = 'BIG00001';
        $endCode = 'BIG' . str_pad((string)$overMaxAdd, 5, '0', STR_PAD_LEFT);
        $bigRange = Voucher::generateCodeRange($startCode, $endCode);

        $this->assertCount($overMaxAdd, $bigRange);

        Auth::login($this->centreUser);
        foreach ($bigRange as $code) {
            $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
            $voucher->applyTransition('dispatch');
        }
        Auth::logout();

        $response = $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $startCode, 'end' => $endCode]);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Failed adding more than ' . config('arc.bundle_max_voucher_append') . ' vouchers/',
        ));

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);

        $this->assertSame(0, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testAddingARangeWithMissingIntermediateCodesFlashesCodesError(): void
    {
        // StoreAppendBundleRequest validates that `start` and `end` exist:vouchers,code,
        // so we cannot trigger the controller's 'codes' error with a non-existent single
        // code — validation rejects it before the controller runs.
        //
        // The correct path: submit a range whose endpoints both exist in the DB but whose
        // interior contains codes that were never created.  generateCodeRange produces
        // ['GAP00001', 'GAP00002', 'GAP00003']; addVouchers finds only the two endpoints;
        // alterVouchers reports GAP00002 under the 'codes' error key.
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        Auth::login($this->centreUser);
        $start = factory(Voucher::class)->state('printed')->create(['code' => 'GAP00001']);
        $start->applyTransition('dispatch');
        $end = factory(Voucher::class)->state('printed')->create(['code' => 'GAP00003']);
        $end->applyTransition('dispatch');
        // GAP00002 is deliberately never created.
        Auth::logout();

        $response = $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => 'GAP00001', 'end' => 'GAP00003']);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/These codes are invalid: GAP00002/',
        ));
    }

    public function testAddingAnAlreadyDisbursedVoucherFlashesDisbursedError(): void
    {
        // Disburse a voucher on a second registration so its bundle has disbursed_at set.
        $otherReg = factory(Registration::class)->create(['centre_id' => $this->centre->id]);
        $otherBundle = $otherReg->currentBundle();

        Auth::login($this->centreUser);
        $this->attachDispatchedVoucherToBundle('DSB00001', $otherBundle);
        Auth::logout();

        $otherBundle->disbursed_at = Carbon::now();
        $otherBundle->collectingCarer()->associate($otherReg->family->carers->first());
        $otherBundle->disbursingCentre()->associate($this->centre);
        $otherBundle->disbursingUser()->associate($this->centreUser);
        $otherBundle->save();

        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => 'DSB00001']);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/These vouchers have been given out: DSB00001/',
        ));
        $this->assertSame(0, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testAddingAVoucherInAUsedStateFlashesUsedError(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => 'USED00001']);
        $voucher->applyTransition('dispatch');
        $trader = factory(Trader::class)->create();
        $voucher->trader_id = $trader->id;
        $voucher->applyTransition('collect');   // now in 'collected' — cannot be re-collected
        Auth::logout();

        $response = $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => 'USED00001']);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/These vouchers have already been used: USED00001/',
        ));
        $this->assertSame(0, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testICannotAddAVoucherAllocatedInACentreIHaveAccessTo(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        // Allocate the first test voucher to the primary registration.
        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        // Create a second registration in the same centre and try to claim the same voucher.
        $registrationTwo = factory(Registration::class)->create(['centre_id' => $this->centre->id]);
        $postRoute2 = route('store.registration.vouchers.post', ['registration' => $registrationTwo->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($this->managerRoute($registrationTwo))
            ->post($postRoute2, ['start' => $this->testCodes[0]]);

        $this->assertSame(0, $registrationTwo->currentBundle()->vouchers()->count());
        $response->seeInSession('error_messages');

        $entity = Family::getAlias($this->programme);
        $expectedPattern = '~These vouchers are currently allocated to a different ' . $entity
            . '. Click on the voucher number to view the other ' . $entity
            . '\'s record: <a href="' . $this->managerRoute() . '">' . $this->testCodes[0] . '</a>~';

        $this->assertTrue($this->hasMatchingErrorMessage(Session::get('error_messages'), $expectedPattern));

        $this->followRedirects()
            ->seeInElement(
                'div[class="alert-message error"]',
                'Click on the voucher number to view the other ' . $entity
                . '\'s record: <a href="' . $this->managerRoute() . '">' . $this->testCodes[0] . '</a>',
            );
    }

    public function testICannotAddAVoucherAllocatedInACentreIDoNotHaveAccessTo(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        // Allocate the second test voucher to the primary registration.
        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[1]]);

        // Build a completely separate sponsor / centre / user / registration hierarchy.
        $sponsor2 = factory(Sponsor::class)->create();
        $centre2 = factory(Centre::class)->create(['sponsor_id' => $sponsor2->id]);
        $centreUser2 = factory(CentreUser::class)->create([
            'name' => 'second test user',
            'email' => 'testuser2@example.com',
            'password' => bcrypt('test_user_pass2'),
            'role' => 'centre_user',
        ]);
        $centreUser2->centres()->attach($centre2->id, ['homeCentre' => true]);

        $registrationTwo = factory(Registration::class)->create(['centre_id' => $centre2->id]);
        $postRoute2 = route('store.registration.vouchers.post', ['registration' => $registrationTwo->id]);

        $response = $this->actingAs($centreUser2, 'store')
            ->visit($this->managerRoute($registrationTwo))
            ->post($postRoute2, ['start' => $this->testCodes[1]]);

        $this->assertSame(0, $registrationTwo->currentBundle()->vouchers()->count());
        $response->seeInSession('error_messages');

        $entity = Family::getAlias($this->programme);
        $expectedPattern = '~These vouchers are allocated to a different ' . $entity
            . ' in a centre you can\'t access: ' . $this->testCodes[1] . '~';

        $this->assertTrue($this->hasMatchingErrorMessage(Session::get('error_messages'), $expectedPattern));

        $this->followRedirects()
            ->seeInElement(
                'div[class="alert-message error"]',
                'These vouchers are allocated to a different ' . $entity
                . ' in a centre you can\'t access: ' . $this->testCodes[1],
            );
    }

    // =========================================================================
    // addVouchersToCurrentBundle() — input cleaning
    // =========================================================================

    public function testItCanAcceptAndCleanVouchersWithSpacesIn(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);
        $voucherCode = $this->testCodes[0];
        $voucherCode = substr_replace($voucherCode, ' ', rand(0, strlen($voucherCode)), 0);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $voucherCode]);

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);

        $this->assertSame(1, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testItHasSparseFormDataCleanedBeforeProcessing(): void
    {
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        // A null end should be treated as a single-code add.
        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0], 'end' => null]);

        $lastVoucher = $this->registration->currentBundle()->vouchers()->orderByDesc('id')->first();
        $this->assertSame($this->testCodes[0], $lastVoucher->code);

        // A blank-string end should also be treated as a single-code add.
        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[1], 'end' => '']);

        $lastVoucher = $this->registration->currentBundle()->vouchers()->orderByDesc('id')->first();
        $this->assertSame($this->testCodes[1], $lastVoucher->code);
    }

    // =========================================================================
    // removeAllVouchersFromCurrentBundle()
    // =========================================================================

    public function testICanRemoveAllVouchersFromTheCurrentBundle(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $deleteCodes = ['TST0123455', 'TST0123456', 'TST0123457'];

        Auth::login($this->centreUser);
        foreach ($deleteCodes as $code) {
            $this->attachDispatchedVoucherToBundle($code, $currentBundle);
        }
        Auth::logout();

        $this->assertSame(count($deleteCodes), $currentBundle->vouchers()->count());

        $deleteRoute = route('store.registration.vouchers.delete', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $currentBundle->refresh();
        $this->assertSame(0, $currentBundle->vouchers()->count());

        Voucher::whereIn('code', $deleteCodes)
            ->each(fn (Voucher $v) => $this->assertNull($v->bundle_id));
    }

    public function testICanDeleteTheCurrentBundle(): void
    {
        // Alias preserved for backwards compatibility — delegates to the renamed test body.
        $this->testICanRemoveAllVouchersFromTheCurrentBundle();
    }

    public function testRemovingAllVouchersFlashesGenericSuccessMessage(): void
    {
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        $this->attachDispatchedVoucherToBundle('REM00001', $currentBundle);
        Auth::logout();

        $deleteRoute = route('store.registration.vouchers.delete', ['registration' => $this->registration->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $response->seeInSession('message');
        $this->assertSame('Vouchers updated', Session::get('message'));
    }

    // =========================================================================
    // removeVoucherFromCurrentBundle()
    // =========================================================================

    public function testICanDeleteANamedVoucher(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $bundledCodes = ['TST0123455', 'TST0123456', 'TST0123457'];

        Auth::login($this->centreUser);
        foreach ($bundledCodes as $code) {
            $this->attachDispatchedVoucherToBundle($code, $currentBundle);
        }
        Auth::logout();

        $this->assertSame(count($bundledCodes), $currentBundle->vouchers()->count());

        $target = $currentBundle->vouchers()->first();
        $deleteRoute = route('store.registration.voucher.delete', [
            'registration' => $this->registration->id,
            'voucher' => $target->id,
        ]);

        $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $currentBundle->refresh();
        $this->assertSame(count($bundledCodes) - 1, $currentBundle->vouchers()->count());

        $target->refresh();
        $this->assertNull($target->bundle_id);
        $this->assertSame('dispatched', $target->currentstate);
    }

    public function testRemovingANamedVoucherFlashesGenericSuccessMessage(): void
    {
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        $voucher = $this->attachDispatchedVoucherToBundle('REM00002', $currentBundle);
        Auth::logout();

        $deleteRoute = route('store.registration.voucher.delete', [
            'registration' => $this->registration->id,
            'voucher' => $voucher->id,
        ]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $response->seeInSession('message');
        $this->assertSame('Vouchers updated', Session::get('message'));
    }

    public function testICannotRemoveAVoucherThatBelongsToADifferentBundle(): void
    {
        // Attach a voucher to a *different* registration's bundle.
        $otherReg = factory(Registration::class)->create(['centre_id' => $this->centre->id]);
        $otherBundle = $otherReg->currentBundle();

        Auth::login($this->centreUser);
        $voucher = $this->attachDispatchedVoucherToBundle('OTH00001', $otherBundle);
        Auth::logout();

        // Attempt to remove that voucher through *this* registration's route.
        $deleteRoute = route('store.registration.voucher.delete', [
            'registration' => $this->registration->id,
            'voucher' => $voucher->id,
        ]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/These vouchers do not belong to this bundle: OTH00001/',
        ));

        // Voucher must still belong to the other bundle.
        $voucher->refresh();
        $this->assertSame($otherBundle->id, $voucher->bundle_id);
    }

    // =========================================================================
    // pickup() — disbursal without state-machine transition
    // =========================================================================

    public function testICanDisburseABundleViaPickup(): void
    {
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        $this->attachDispatchedVoucherToBundle('PKP00001', $currentBundle);
        Auth::logout();

        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, $this->defaultDisbursalData());

        $this->followRedirects()
            ->seePageIs(route('store.registration.index'))
            ->assertResponseStatus(200);

        $currentBundle->refresh();
        $this->assertNotNull($currentBundle->disbursed_at);
        $this->assertSame(
            Carbon::now()->startOfWeek()->toDateString(),
            $currentBundle->disbursed_at->toDateString(),
        );
    }

    public function testSuccessfulPickupRedirectsToRegistrationIndex(): void
    {
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        $this->attachDispatchedVoucherToBundle('PKP00002', $currentBundle);
        Auth::logout();

        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, $this->defaultDisbursalData());

        $this->followRedirects()
            ->seePageIs(route('store.registration.index'))
            ->assertResponseStatus(200);
    }

    public function testSuccessfulPickupFlashesMessageNamingCarerAndVoucherCount(): void
    {
        $currentBundle = $this->registration->currentBundle();

        Auth::login($this->centreUser);
        $this->attachDispatchedVoucherToBundle('PKP00003', $currentBundle);
        Auth::logout();

        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, $this->defaultDisbursalData());

        $response->seeInSession('message');

        $carer = $this->registration->family->carers->first();
        $message = Session::get('message');

        $this->assertStringContainsString('1', $message);
        $this->assertStringContainsString('voucher', $message);
        $this->assertStringContainsString($carer->name, $message);
    }

    public function testICannotDisburseAnEmptyBundle(): void
    {
        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($this->managerRoute())
            ->put($putRoute, $this->defaultDisbursalData());

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Action denied on empty bundle/',
        ));

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);
    }

    public function testEmptyBundleDisbursalRedirectsBackToManager(): void
    {
        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->visit($this->managerRoute())
            ->put($putRoute, $this->defaultDisbursalData());

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);
    }

    // =========================================================================
    // collectBundle() — disbursal + state-machine transition
    // =========================================================================

    public function testICanCollectABundleAndTransitionItsVouchers(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $trader = factory(Trader::class)->create();

        // Associate already-dispatched test vouchers with the bundle.
        // Set delivery_id so handleCollect's undelivered-check is bypassed.
        Auth::login($this->centreUser);
        foreach ($this->testCodes as $code) {
            $voucher = Voucher::where('code', $code)->firstOrFail();
            $voucher->delivery_id = 1; // prevents the undelivered branch in handleCollect
            $voucher->bundle()->associate($currentBundle)->save();
        }
        Auth::logout();

        $collectRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $this->actingAs($this->centreUser, 'store')
            ->put($collectRoute, array_merge(
                $this->defaultDisbursalData(),
                ['trader_id' => $trader->id],
            ));

        $this->followRedirects()
            ->seePageIs(route('store.registration.index'))
            ->assertResponseStatus(200);

        $currentBundle->refresh();
        $this->assertNotNull($currentBundle->disbursed_at);

        foreach ($this->testCodes as $code) {
            $this->assertSame('recorded', Voucher::where('code', $code)->first()->currentstate);
        }
    }

    public function testCollectBundleRedirectsToRegistrationIndexOnSuccess(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $trader = factory(Trader::class)->create();

        Auth::login($this->centreUser);
        $voucher = Voucher::where('code', $this->testCodes[0])->firstOrFail();
        $voucher->delivery_id = 1;
        $voucher->bundle()->associate($currentBundle)->save();
        Auth::logout();

        $collectRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $this->actingAs($this->centreUser, 'store')
            ->put($collectRoute, array_merge(
                $this->defaultDisbursalData(),
                ['trader_id' => $trader->id],
            ));

        $this->followRedirects()
            ->seePageIs(route('store.registration.index'))
            ->assertResponseStatus(200);
    }

    public function testCollectBundleRollsBackWhenBundleIsEmpty(): void
    {
        $collectRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );
        $trader = factory(Trader::class)->create();

        $response = $this->actingAs($this->centreUser, 'store')
            ->put($collectRoute, array_merge(
                $this->defaultDisbursalData(),
                ['trader_id' => $trader->id],
            ));

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Action denied on empty bundle/',
        ));

        $this->followRedirects()
            ->seePageIs($this->managerRoute())
            ->assertResponseStatus(200);
    }

    public function testCollectBundleRollsBackDisbursalWhenTransitionFails(): void
    {
        // Push first_delivery_date into the future so handleCollect's undelivered guard
        // evaluates false (future_date <= now is false) and the already-collected voucher
        // reaches the actual transition attempt rather than the undelivered skip-path.
        config(['arc.first_delivery_date' => Carbon::now()->addYear()->toDateString()]);

        $currentBundle = $this->registration->currentBundle();
        $trader = factory(Trader::class)->create();

        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => 'COLT0001']);
        $voucher->applyTransition('dispatch');
        $voucher->trader_id = $trader->id;
        $voucher->applyTransition('collect');
        $voucher->bundle()->associate($currentBundle)->save();
        Auth::logout();

        $collectRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $this->actingAs($this->centreUser, 'store')
            ->post($collectRoute, array_merge(
                $this->defaultDisbursalData(),
                ['trader_id' => $trader->id],
            ));

        // Disbursal must have been rolled back because the transition was denied.
        $currentBundle->refresh();
        $this->assertNull($currentBundle->disbursed_at);
    }

    public function testCollectBundleFlashesTransitionErrorCodesOnFailure(): void
    {
        // Same guard bypass as testCollectBundleRollsBackDisbursalWhenTransitionFails.
        config(['arc.first_delivery_date' => Carbon::now()->addYear()->toDateString()]);

        $currentBundle = $this->registration->currentBundle();
        $trader = factory(Trader::class)->create();

        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => 'COLT0002']);
        $voucher->applyTransition('dispatch');
        $voucher->trader_id = $trader->id;
        $voucher->applyTransition('collect');
        $voucher->bundle()->associate($currentBundle)->save();
        Auth::logout();

        $collectRoute = route(
            'store.registration.vouchers.transitions.collect',
            ['registration' => $this->registration->id]
        );

        $response = $this->actingAs($this->centreUser, 'store')
            ->put($collectRoute, array_merge(
                $this->defaultDisbursalData(),
                ['trader_id' => $trader->id],
            ));

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Voucher state change problem with:/',
        ));
    }
}

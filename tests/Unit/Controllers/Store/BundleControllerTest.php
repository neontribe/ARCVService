<?php

namespace Tests\Unit\Controllers\Store;

use App\Centre;
use App\CentreUser;
use App\Family;
use App\Registration;
use App\Sponsor;
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
    // Adding vouchers to a bundle
    // -------------------------------------------------------------------------

    public function testICanAddSingleVouchers(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);

        $this->assertSame(1, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testICanAddManyVouchers(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, [
                'start' => $this->testCodes[0],
                'end' => $this->testCodes[count($this->testCodes) - 1],
            ]);

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);

        $this->assertSame(count($this->testCodes), $this->registration->currentBundle()->vouchers()->count());
    }

    public function testICannotAddTooManyVouchersToABundle(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
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
            ->seePageIs($route)
            ->assertResponseStatus(200);

        $this->assertSame(0, $this->registration->currentBundle()->vouchers()->count());
    }

    public function testICannotAddAVoucherAllocatedInACentreIHaveAccessTo(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        // Allocate the first test voucher to the primary registration.
        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $this->testCodes[0]]);

        // Create a second registration in the same centre.
        $registrationTwo = factory(Registration::class)->create(['centre_id' => $this->centre->id]);

        $route2 = route('store.registration.voucher-manager', ['registration' => $registrationTwo->id]);
        $postRoute2 = route('store.registration.vouchers.post', ['registration' => $registrationTwo->id]);

        // Attempt to add the already-allocated voucher to the second bundle.
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($route2)
            ->post($postRoute2, ['start' => $this->testCodes[0]]);

        $this->assertSame(0, $registrationTwo->currentBundle()->vouchers()->count());

        $response->seeInSession('error_messages');

        $entity = Family::getAlias($this->programme);
        $expectedPattern = '~These vouchers are currently allocated to a different ' . $entity
            . '. Click on the voucher number to view the other ' . $entity
            . '\'s record: <a href="' . $route . '">' . $this->testCodes[0] . '</a>~';

        $this->assertTrue($this->hasMatchingErrorMessage(Session::get('error_messages'), $expectedPattern));

        $this->followRedirects()
            ->seeInElement(
                'div[class="alert-message error"]',
                'Click on the voucher number to view the other ' . $entity
                . '\'s record: <a href="' . $route . '">' . $this->testCodes[0] . '</a>',
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

        $route2 = route('store.registration.voucher-manager', ['registration' => $registrationTwo->id]);
        $postRoute2 = route('store.registration.vouchers.post', ['registration' => $registrationTwo->id]);

        $response = $this->actingAs($centreUser2, 'store')
            ->visit($route2)
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

    // -------------------------------------------------------------------------
    // Input cleaning
    // -------------------------------------------------------------------------

    public function testItCanAcceptAndCleanVouchersWithSpacesIn(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $postRoute = route('store.registration.vouchers.post', ['registration' => $this->registration->id]);

        // Insert a space at a random position in the voucher code.
        $voucherCode = $this->testCodes[0];
        $voucherCode = substr_replace($voucherCode, ' ', rand(0, strlen($voucherCode)), 0);

        $this->actingAs($this->centreUser, 'store')
            ->post($postRoute, ['start' => $voucherCode]);

        $this->followRedirects()
            ->seePageIs($route)
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

    // -------------------------------------------------------------------------
    // Sync (PUT)
    // -------------------------------------------------------------------------

    public function testICanSyncAnArrayOfVouchers(): void
    {
        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        // Sync a single voucher.
        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, ['vouchers' => [$this->testCodes[0]]]);

        $currentBundle = $this->registration->currentBundle();
        $this->assertSame(1, $currentBundle->vouchers()->count());

        // Re-sync with all three vouchers.
        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, ['vouchers' => $this->testCodes]);

        $currentBundle->refresh();
        $this->assertSame(count($this->testCodes), $currentBundle->vouchers()->count());

        // Sending no vouchers key at all leaves the bundle unchanged.
        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute);

        $currentBundle->refresh();
        $this->assertSame(count($this->testCodes), $currentBundle->vouchers()->count());

        // A single empty string in the vouchers array clears the bundle.
        $this->actingAs($this->centreUser, 'store')
            ->put($putRoute, ['vouchers' => ['']]);

        $currentBundle->refresh();
        $this->assertSame(0, $currentBundle->vouchers()->count());
    }

    // -------------------------------------------------------------------------
    // Delete operations
    // -------------------------------------------------------------------------

    public function testICanDeleteTheCurrentBundle(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $deleteCodes = ['TST0123455', 'TST0123456', 'TST0123457'];

        Auth::login($this->centreUser);
        foreach ($deleteCodes as $code) {
            $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
            $voucher->applyTransition('dispatch');
            $voucher->bundle()->associate($currentBundle)->save();
        }
        Auth::logout();

        $this->assertSame(count($deleteCodes), $currentBundle->vouchers()->count());

        $deleteRoute = route('store.registration.vouchers.delete', ['registration' => $this->registration->id]);

        $this->actingAs($this->centreUser, 'store')
            ->delete($deleteRoute);

        $currentBundle->refresh();
        $this->assertSame(0, $currentBundle->vouchers()->count());

        // Every previously-bundled voucher should have a null bundle_id.
        Voucher::whereIn('code', $deleteCodes)
            ->each(function (Voucher $v) {
                return $this->assertNull($v->bundle_id);
            });
    }

    public function testICanDeleteANamedVoucher(): void
    {
        $currentBundle = $this->registration->currentBundle();
        $bundledCodes = ['TST0123455', 'TST0123456', 'TST0123457'];

        Auth::login($this->centreUser);
        foreach ($bundledCodes as $code) {
            $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
            $voucher->applyTransition('dispatch');
            $voucher->bundle()->associate($currentBundle)->save();
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

    // -------------------------------------------------------------------------
    // Disbursement
    // -------------------------------------------------------------------------

    public function testICannotDisburseAnEmptyBundle(): void
    {
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $putRoute = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);

        $data = [
            'collected_at' => $this->centre->id,
            'collected_on' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'collected_by' => $this->registration->family->carers->first()->id,
        ];

        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->put($putRoute, $data);

        $response->seeInSession('error_messages');
        $this->assertTrue($this->hasMatchingErrorMessage(
            Session::get('error_messages'),
            '/Action denied on empty bundle/',
        ));

        $this->followRedirects()
            ->seePageIs($route)
            ->assertResponseStatus(200);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Search an array of session error messages for one matching the given regular expression.
     * Handles both plain strings and HtmlString-derived arrays with a 'html' key.
     *
     * @param array<int, string|array<string, string>> $errorMessages
     */
    private function hasMatchingErrorMessage(array $errorMessages, string $regex): bool
    {
        foreach ($errorMessages as $error) {
            $string = is_array($error) && array_key_exists('html', $error)
                ? $error['html']
                : (string)$error;

            if (preg_match($regex, $string)) {
                return true;
            }
        }

        return false;
    }
}

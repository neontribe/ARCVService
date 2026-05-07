<?php

namespace Tests\Unit\Models;

use App\AdminUser;
use App\StateToken;
use App\User;
use App\VoucherState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class StateTokenModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Setup / teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        // Pin time so that subDays() calculations are deterministic regardless
        // of when the suite runs — avoids failures at midnight or month boundaries.
        Carbon::setTestNow(Carbon::parse('2024-06-12 12:00:00'));

        // Scope tests and checkIfOutstandingPayments read this value.
        // Individual tests override it inline when they need a different window.
        Config::set('arc.payment_window_days', 21);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Existing model tests — unchanged
    // =========================================================================

    public function testFillableAttributes(): void
    {
        $expected = ['uuid', 'user_id', 'admin_user_id'];

        $this->assertSame($expected, (new StateToken())->getFillable());
    }

    public function testGenerateUnusedTokenReturnsValidUuid(): void
    {
        $token = StateToken::generateUnusedToken();

        $this->assertTrue(Str::isUuid($token));
    }

    public function testGenerateUnusedTokenReturnsUniqueValues(): void
    {
        $first = StateToken::generateUnusedToken();
        $second = StateToken::generateUnusedToken();

        $this->assertNotSame($first, $second);
    }

    public function testGenerateUnusedTokenSkipsExistingUuids(): void
    {
        $existing = factory(StateToken::class)->create();

        $fresh = (string)Str::uuid();
        Str::createUuidsUsing(function () use ($existing, $fresh, &$called) {
            if (!$called) {
                $called = true;
                return new class($existing->uuid) {
                    public function __construct(private string $value)
                    {
                    }

                    public function toString(): string
                    {
                        return $this->value;
                    }

                    public function __toString(): string
                    {
                        return $this->value;
                    }
                };
            }
            return new class($fresh) {
                public function __construct(private string $value)
                {
                }

                public function toString(): string
                {
                    return $this->value;
                }

                public function __toString(): string
                {
                    return $this->value;
                }
            };
        });

        $generated = StateToken::generateUnusedToken();

        Str::createUuidsNormally();

        $this->assertSame($fresh, $generated);
        $this->assertNotSame($existing->uuid, $generated);
    }

    public function testCanCreateAndPersistTokenWithGeneratedUuid(): void
    {
        $uuid = StateToken::generateUnusedToken();
        $token = factory(StateToken::class)->create(['uuid' => $uuid]);

        $this->assertDatabaseHas('state_tokens', ['uuid' => $uuid]);
        $this->assertSame($uuid, $token->fresh()->uuid);
    }

    public function testIsUsedTokenReturnsTrueForExistingUuid(): void
    {
        $token = factory(StateToken::class)->create();

        $this->assertTrue(StateToken::isUsedToken($token->uuid));
    }

    public function testIsUsedTokenReturnsFalseForUnknownUuid(): void
    {
        $this->assertFalse(StateToken::isUsedToken((string)Str::uuid()));
    }

    public function testDuplicateUuidThrowsQueryException(): void
    {
        $token = factory(StateToken::class)->create();

        $this->expectException(QueryException::class);

        factory(StateToken::class)->create(['uuid' => $token->uuid]);
    }

    public function testVoucherStatesIsHasManyRelation(): void
    {
        $relation = (new StateToken())->voucherStates();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(VoucherState::class, $relation->getRelated());
    }

    public function testUserIsBelongsToRelation(): void
    {
        $relation = (new StateToken())->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testAdminUserIsBelongsToRelation(): void
    {
        $relation = (new StateToken())->adminUser();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(AdminUser::class, $relation->getRelated());
    }

    public function testBelongsToUser(): void
    {
        $user = factory(User::class)->create();
        $token = factory(StateToken::class)->create(['user_id' => $user->id]);

        $this->assertTrue($token->user->is($user));
    }

    public function testBelongsToAdminUser(): void
    {
        $admin = factory(AdminUser::class)->create();
        $token = factory(StateToken::class)->create(['admin_user_id' => $admin->id]);

        $this->assertTrue($token->adminUser->is($admin));
    }

    public function testHasManyVoucherStates(): void
    {
        $token = factory(StateToken::class)->create();
        factory(VoucherState::class, 3)->create(['state_token_id' => $token->id]);

        $this->assertCount(3, $token->voucherStates);
        $this->assertContainsOnlyInstancesOf(VoucherState::class, $token->voucherStates);
    }

    public function testUserIdAndAdminUserIdAreNullable(): void
    {
        $token = factory(StateToken::class)->create([
            'user_id' => null,
            'admin_user_id' => null,
        ]);

        $this->assertNull($token->user_id);
        $this->assertNull($token->admin_user_id);
        $this->assertDatabaseHas('state_tokens', ['id' => $token->id, 'user_id' => null]);
    }

    // =========================================================================
    // scopePending
    // =========================================================================

    public function testPendingScopeIncludesTokensWithNullAdminUserId(): void
    {
        $pending = factory(StateToken::class)->create(['admin_user_id' => null]);

        $this->assertTrue(StateToken::pending()->get()->contains($pending));
    }

    public function testPendingScopeExcludesTokensWithAdminUserId(): void
    {
        $admin = factory(AdminUser::class)->create();
        $reimbursed = factory(StateToken::class)->create(['admin_user_id' => $admin->id]);

        $this->assertFalse(StateToken::pending()->get()->contains($reimbursed));
    }

    // =========================================================================
    // scopeReimbursed
    // =========================================================================

    public function testReimbursedScopeIncludesTokensWithAdminUserId(): void
    {
        $admin = factory(AdminUser::class)->create();
        $reimbursed = factory(StateToken::class)->create(['admin_user_id' => $admin->id]);

        $this->assertTrue(StateToken::reimbursed()->get()->contains($reimbursed));
    }

    public function testReimbursedScopeExcludesTokensWithNullAdminUserId(): void
    {
        $pending = factory(StateToken::class)->create(['admin_user_id' => null]);

        $this->assertFalse(StateToken::reimbursed()->get()->contains($pending));
    }

    // =========================================================================
    // scopeWithinPaymentWindow
    //
    // The scope uses '>=' so a token created exactly on the boundary IS included.
    // $from = Carbon::now()->startOfDay()->subDays(payment_window_days)
    // With pinned time 2024-06-12 12:00 and 21 days, $from = 2024-05-22 00:00:00.
    // =========================================================================

    public function testWithinPaymentWindowIncludesTokensCreatedWithinConfiguredDays(): void
    {
        $recent = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $this->assertTrue(StateToken::withinPaymentWindow()->get()->contains($recent));
    }

    public function testWithinPaymentWindowExcludesTokensCreatedBeforeConfiguredDays(): void
    {
        $old = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $this->assertFalse(StateToken::withinPaymentWindow()->get()->contains($old));
    }

    public function testWithinPaymentWindowIncludesTokenCreatedExactlyOnTheBoundary(): void
    {
        // created_at == $from satisfies '>=' and must be included.
        $boundary = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->startOfDay()->subDays(21),
        ]);

        $this->assertTrue(StateToken::withinPaymentWindow()->get()->contains($boundary));
    }

    public function testWithinPaymentWindowExcludesTokenCreatedOneSecondBeforeTheBoundary(): void
    {
        $justOutside = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->startOfDay()->subDays(21)->subSecond(),
        ]);

        $this->assertFalse(StateToken::withinPaymentWindow()->get()->contains($justOutside));
    }

    public function testWithinPaymentWindowAcceptsACustomStartDate(): void
    {
        // Token is 40 days old — outside the default 21-day window but inside
        // a 50-day window supplied as an explicit date argument.
        $old = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->subDays(40),
        ]);

        $customDate = Carbon::now()->subDays(50)->startOfDay();

        $this->assertTrue(StateToken::withinPaymentWindow($customDate)->get()->contains($old));
    }

    public function testWithinPaymentWindowRespectsPaymentWindowDaysConfig(): void
    {
        // A token 8 days old is inside a 10-day window but outside a 5-day window.
        $token = factory(StateToken::class)->create([
            'created_at' => Carbon::now()->subDays(8),
        ]);

        Config::set('arc.payment_window_days', 10);
        $this->assertTrue(StateToken::withinPaymentWindow()->get()->contains($token));

        Config::set('arc.payment_window_days', 5);
        $this->assertFalse(StateToken::withinPaymentWindow()->get()->contains($token));
    }

    // =========================================================================
    // scopeWithPaymentRelations
    // =========================================================================

    public function testWithPaymentRelationsEagerLoadsUserRelationship(): void
    {
        factory(StateToken::class)->create(['user_id' => factory(User::class)->create()->id]);

        $token = StateToken::withPaymentRelations()->first();

        $this->assertTrue($token->relationLoaded('user'));
    }

    public function testWithPaymentRelationsEagerLoadsVoucherStatesRelationship(): void
    {
        factory(StateToken::class)->create();

        $token = StateToken::withPaymentRelations()->first();

        $this->assertTrue($token->relationLoaded('voucherStates'));
    }

    // =========================================================================
    // checkIfOutstandingPayments
    // =========================================================================

    public function testCheckIfOutstandingPaymentsReturnsTrueWhenPendingTokenExistsWithinWindow(): void
    {
        factory(StateToken::class)->create([
            'admin_user_id' => null,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $this->assertTrue(StateToken::checkIfOutstandingPayments());
    }

    public function testCheckIfOutstandingPaymentsReturnsFalseWhenNoTokensExist(): void
    {
        $this->assertFalse(StateToken::checkIfOutstandingPayments());
    }

    public function testCheckIfOutstandingPaymentsReturnsFalseWhenAllTokensAreReimbursed(): void
    {
        $admin = factory(AdminUser::class)->create();
        factory(StateToken::class)->create([
            'admin_user_id' => $admin->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $this->assertFalse(StateToken::checkIfOutstandingPayments());
    }

    public function testCheckIfOutstandingPaymentsReturnsFalseWhenPendingTokensAreOutsideWindow(): void
    {
        factory(StateToken::class)->create([
            'admin_user_id' => null,
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $this->assertFalse(StateToken::checkIfOutstandingPayments());
    }
}

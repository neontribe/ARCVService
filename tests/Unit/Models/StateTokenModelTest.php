<?php

namespace Tests\Unit\Models;

use App\AdminUser;
use App\StateToken;
use App\User;
use App\VoucherState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StateTokenModelTest extends TestCase
{
    use RefreshDatabase;

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
}

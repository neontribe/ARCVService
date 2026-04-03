<?php

namespace Tests\Unit\Traits;

use App\Traits\Retirable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Stubs\TestRetirableModel;
use Tests\TestCase;

/**
 * Intentionally omits SoftDeletes to verify the boot-time guard.
 * Kept here rather than in Stubs as it has no use outside this test.
 */
class RetirableWithoutSoftDeletesStub extends Model
{
    use Retirable;

    protected $table = 'retirable_stubs';
}

class RetirableTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Schema setup
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('retirable_stubs', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('retired_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('retirable_stubs');

        // Clear Eloquent's static boot cache so each test begins with a clean
        // boot cycle — essential for the boot guard test to be repeatable.
        Model::clearBootedModels();

        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeStub(array $attributes = []): TestRetirableModel
    {
        return TestRetirableModel::create(array_merge([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('secret'),
        ], $attributes));
    }

    // -----------------------------------------------------------------------
    // Boot guard
    // -----------------------------------------------------------------------


    public function testBootRetirableThrowsLogicExceptionIfSoftDeletesIsNotUsed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must use SoftDeletes');

        new RetirableWithoutSoftDeletesStub();
    }

    // -----------------------------------------------------------------------
    // Cast registration
    // -----------------------------------------------------------------------


    public function testRetiredAtIsAutomaticallyCastToDatetime(): void
    {
        $stub = $this->makeStub();
        $stub->retired_at = now();

        $this->assertInstanceOf(Carbon::class, $stub->retired_at);
    }

    // -----------------------------------------------------------------------
    // retire()
    // -----------------------------------------------------------------------


    public function testRetireSetsRetiredAtToCurrentTime(): void
    {
        $stub = $this->makeStub();

        $stub->retire();

        $this->assertNotNull($stub->fresh()->retired_at);
    }


    public function testRetireSoftDeletesTheModel(): void
    {
        $stub = $this->makeStub();

        $stub->retire();

        $this->assertSoftDeleted('retirable_stubs', ['id' => $stub->id]);
    }


    public function testRetireReplacesEachFieldDeclaredInRetirableFields(): void
    {
        $stub = $this->makeStub();
        $originalEmail = $stub->email;
        $originalPassword = $stub->password;

        $stub->retire();

        $fresh = TestRetirableModel::withTrashed()->find($stub->id);

        $this->assertNotSame($originalEmail, $fresh->email);
        $this->assertNotSame($originalPassword, $fresh->password);
        $this->assertNull($fresh->remember_token);
    }


    public function testRetireStoresAValidEmailPlaceholder(): void
    {
        $stub = $this->makeStub();

        $stub->retire();

        $email = TestRetirableModel::withTrashed()->find($stub->id)->email;

        $this->assertStringStartsWith('retired_', $email);
        $this->assertStringEndsWith('@retired.invalid', $email);
    }


    public function testRetireStoresAHashedPasswordNotPlainText(): void
    {
        $stub = $this->makeStub();

        $stub->retire();

        $password = TestRetirableModel::withTrashed()->find($stub->id)->password;

        $this->assertTrue(Hash::isHashed($password));
    }


    public function testRetireIsIdempotentAndDoesNotChangeFieldsOnSecondCall(): void
    {
        $stub = $this->makeStub();
        $stub->retire();

        $afterFirst = TestRetirableModel::withTrashed()->find($stub->id);
        $emailAfterFirst = $afterFirst->email;
        $retiredAtAfterFirst = $afterFirst->retired_at->toDateTimeString();

        $stub->retire();

        $afterSecond = TestRetirableModel::withTrashed()->find($stub->id);

        $this->assertSame($emailAfterFirst, $afterSecond->email);
        $this->assertSame($retiredAtAfterFirst, $afterSecond->retired_at->toDateTimeString());
    }


    public function testTwoSeparatelyRetiredModelsReceiveUniqueEmailPlaceholders(): void
    {
        $first = $this->makeStub(['email' => 'first@example.com']);
        $second = $this->makeStub(['email' => 'second@example.com']);

        $first->retire();
        $second->retire();

        $emailFirst = TestRetirableModel::withTrashed()->find($first->id)->email;
        $emailSecond = TestRetirableModel::withTrashed()->find($second->id)->email;

        $this->assertNotSame($emailFirst, $emailSecond);
    }

    // -----------------------------------------------------------------------
    // isRetired()
    // -----------------------------------------------------------------------


    public function testIsRetiredReturnsFalseForAnActiveModel(): void
    {
        $stub = $this->makeStub();

        $this->assertFalse($stub->isRetired());
    }


    public function testIsRetiredReturnsTrueAfterRetirement(): void
    {
        $stub = $this->makeStub();

        $stub->retire();

        $this->assertTrue($stub->isRetired());
    }

    // -----------------------------------------------------------------------
    // Restore guard (restoring event)
    // -----------------------------------------------------------------------


    public function testRestoringARetiredModelThrowsADomainException(): void
    {
        $stub = $this->makeStub();
        $stub->retire();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot be restored');

        TestRetirableModel::withTrashed()->find($stub->id)->restore();
    }


    public function testRestoringAMerelySoftDeletedModelSucceeds(): void
    {
        $stub = $this->makeStub();
        $stub->delete();

        TestRetirableModel::withTrashed()->find($stub->id)->restore();

        $this->assertNotSoftDeleted('retirable_stubs', ['id' => $stub->id]);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------


    public function testScopeRetiredReturnsOnlyRetiredModels(): void
    {
        $active = $this->makeStub(['email' => 'active@example.com']);
        $deleted = $this->makeStub(['email' => 'deleted@example.com']);
        $retired = $this->makeStub(['email' => 'retired@example.com']);

        $deleted->delete();
        $retired->retire();

        $results = TestRetirableModel::retired()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($retired));
    }


    public function testScopeActiveExcludesRetiredAndSoftDeletedModels(): void
    {
        $active = $this->makeStub(['email' => 'active@example.com']);
        $deleted = $this->makeStub(['email' => 'deleted@example.com']);
        $retired = $this->makeStub(['email' => 'retired@example.com']);

        $deleted->delete();
        $retired->retire();

        $results = TestRetirableModel::active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($active));
    }
}

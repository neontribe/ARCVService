<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Trader;
use App\User;

class UserModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $users;
    protected $traders;
    protected function setUp(): void
    {
        parent::setUp();
        $this->users = factory(User::class, 2)->create();
        $this->traders = factory(Trader::class, 'withnullable', 5)->create();

        $this->users[0]->traders()->sync([1,2,3]);
        $this->users[1]->traders()->sync([4,5]);
    }

    public function testUserBelongsToManyTraders()
    {
        $this->assertCount(3, $this->users[0]->traders);
        $this->assertCount(2, $this->users[1]->traders);
    }

    public function testSoftDeleteUser()
    {
        $this->users[0]->delete();
        $this->assertCount(2, User::withTrashed()->get());
        $this->assertCount(1, User::all());
    }

    public function testCheckIfTraderBelongsToUser()
    {
        $trader = $this->traders[0];
        $this->assertTrue($this->users[0]->hasTrader($trader));
        $this->assertFalse($this->users[1]->hasTrader($trader));
    }
}

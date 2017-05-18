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
    protected function setUp()
    {
        parent::setUp();
        $this->users = factory(User::class, 2)->create();
        $this->traders = factory(Trader::class, 'withnullable', 5)->create();

        $this->users[0]->traders()->sync([1,2,3]);
        $this->users[1]->traders()->sync([4,5]);
    }

    public function testUserBelongsToManyTraders()
    {
    }


    public function testSoftDeleteUser()
    {
        $this->users[0]->delete();
        $this->assertCount(2, User::withTrashed()->get());
        $this->assertCount(1, User::all());
    }

}

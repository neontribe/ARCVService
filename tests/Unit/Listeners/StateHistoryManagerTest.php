<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\User;
use App\AdminUser;
use App\CentreUser;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StateHistoryManagerTest extends TestCase
{

    use DatabaseMigrations;

    protected $adminUser;
    protected $centreUser;
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        $this->user = factory(User::class)->create();
    }

    /** @test */
    public function testStateIsUpdatedAsExpected()
    {
        Auth::login($this->adminUser);
        // create a voucher as printed

        $v = factory(Voucher::class, 'requested')->create();
        $v->applyTransition('order');
        $v->applyTransition('print');
        $v->applyTransition('dispatch');

        $state = $v->history()->get(null)->last();
        $this->assertEquals(Auth::user()->id, $state->user_id);
        $this->assertEquals(class_basename(Auth::user()), $state->user_type);

        Auth::login($this->centreUser);
        $v->applyTransition('allocate');

        $state = $v->history()->get(null)->last();
        $this->assertEquals(Auth::user()->id, $state->user_id);
        $this->assertEquals(class_basename(Auth::user()), $state->user_type);

        // transition voucher from allocated to collected
        Auth::login($this->user);
        $v->applyTransition('collect');

        $state = $v->history()->get(null)->last();
        $this->assertEquals(Auth::user()->id, $state->user_id);
        $this->assertEquals(class_basename(Auth::user()), $state->user_type);
    }
}
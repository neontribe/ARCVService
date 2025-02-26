<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\User;
use App\AdminUser;
use App\CentreUser;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StateHistoryManagerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $adminUser;
    protected CentreUser $centreUser;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        $this->user = factory(User::class)->create();
    }

    public function testStateIsUpdatedAsExpected(): void
    {
        Auth::login($this->adminUser);

        // create a voucher as printed
        $v = factory(Voucher::class)->state('printed')->create();

        Auth::login($this->centreUser);
        $v->applyTransition('dispatch');

        $state = $v->history()->get("*")->last();
        $this->assertEquals(Auth::user()->id, $state->user_id);
        $this->assertEquals(class_basename(Auth::user()), $state->user_type);

        // transition voucher from dispatched to collected
        Auth::login($this->user);
        $v->applyTransition('collect');

        $state = $v->history()->get("*")->last();
        $this->assertEquals(Auth::user()->id, $state->user_id);
        $this->assertEquals(class_basename(Auth::user()), $state->user_type);
    }
}

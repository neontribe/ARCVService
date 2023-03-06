<?php

namespace Tests\Unit\Routes;

use App\AdminUser;
use App\Market;
use App\Trader;
use App\User;
use App\Voucher;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DataRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $vouchers;
    protected $users;
    protected $markets;
    protected $traders;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vouchers = factory(Voucher::class, 10)->create();
        $this->users = factory(User::class, 2)->create();
        $this->markets = factory(Market::class, 2)->create();
        $this->traders = factory(Trader::class, 5)->create();
        $this->admin = factory(AdminUser::class)->create();
    }

    // These routes are not public.
    public function testVouchersIndexRouteNotAuthd()
    {
        $this->get(route('data.vouchers.index'))
            ->assertStatus(302);
    }

    // Admin Users can see the data.
    public function testVouchersIndexRouteAuthdAdmin()
    {
        // Check we don't have timezone info in the dates
        $this->assertStringNotContainsString('T', $this->vouchers[0]->created_at);
        $this->assertStringNotContainsString('Z', $this->vouchers[0]->created_at);
        $this->actingAs($this->admin, 'admin')
            ->get(route('data.vouchers.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]]);
    }

    // API Users do not have permission.
    public function testVouchersIndexRouteAuthdApi()
    {
        $this->actingAs($this->users[0], 'api')
            ->get(route('data.vouchers.index'))
            ->assertStatus(302);
    }

    // For the rest - we will just be auth'd as admin for sake of time.
    public function testVouchersShowRoute()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('data.vouchers.show', $this->vouchers[0]))
            ->assertStatus(200)
            ->assertJsonStructure([
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]);
    }

    public function testUsersIndexRoute()
    {
        // Check we don't have timezone info in the dates
        $this->assertStringNotContainsString('T', $this->users[0]->created_at);
        $this->assertStringNotContainsString('Z', $this->users[0]->created_at);
        $this->actingAs($this->admin, 'admin')
            ->get(route('data.users.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'name', 'email'
            ]]);
    }

    public function testMarketsIndexRoute()
    {
        // Check we don't have timezone info in the dates
        $this->assertStringNotContainsString('T', $this->markets[0]->created_at);
        $this->assertStringNotContainsString('Z', $this->markets[0]->created_at);
        $this->actingAs($this->admin, 'admin')
            ->get(route('data.markets.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'name', 'location', 'sponsor_id'
            ]]);
    }

    public function testTradersIndexRoute()
    {
        // Check we don't have timezone info in the dates
        $this->assertStringNotContainsString('T', $this->traders[0]->created_at);
        $this->assertStringNotContainsString('Z', $this->traders[0]->created_at);
        $this->actingAs($this->admin, 'admin')
            ->get(route('data.traders.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'name', 'pic_url', 'market_id'
            ]]);
    }

    // Need to be auth'd for these too.
    public function testProductionRoutes()
    {
        Config::set('app.url', 'https://voucher-admin.alexandrarose.org.uk');
        $models = ['vouchers', 'users', 'markets', 'traders'];
        foreach ($models as $model) {
            $this->actingAs($this->admin, 'admin')
                ->get(route('data.' . $model . '.index'))
                ->assertStatus(418);
        }
        $this->get(route('data.vouchers.show', $this->vouchers[0]))
                ->assertStatus(418);
    }
}

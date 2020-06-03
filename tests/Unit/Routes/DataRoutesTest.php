<?php

namespace Tests\Unit\Routes;

use Auth;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DataRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $vouchers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vouchers = factory(\App\Voucher::class, 10)->create();
        $this->users = factory(\App\User::class, 2)->create();
        $this->markets = factory(\App\Market::class, 2)->create();
        $this->traders = factory(\App\Trader::class, 5)->create();
        $this->admin = factory(\App\AdminUser::class)->create();
    }

    // These routes are not public.
    public function testVouchersIndexRouteNotAuthd()
    {
        $this->get(route('vouchers.index'))
            ->assertStatus(302);
    }

    // Admin Users can see the data.
    public function testVouchersIndexRouteAuthdAdmin()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('vouchers.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]]);
    }

    // API Users do not have permission.
    public function testVouchersIndexRouteAuthdApi()
    {
        $this->actingAs($this->users[0], 'api')
            ->get(route('vouchers.index'))
            ->assertStatus(302);
    }

    // For the rest - we will just be auth'd as admin for sake of time.
    public function testVouchersShowRoute()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('vouchers.show', $this->vouchers[0]))
            ->assertStatus(200)
            ->assertJsonStructure([
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]);
    }

    public function testUsersIndexRoute()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('users.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'name', 'email'
            ]]);
    }

    public function testMarketsIndexRoute()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('markets.index'))
            ->assertStatus(200)
            ->assertJsonStructure([0 => [
                'id', 'name', 'location', 'sponsor_id'
            ]]);
    }

    public function testTradersIndexRoute()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('traders.index'))
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
                ->get(route($model . '.index'))
                ->assertStatus(418);
        }
        $this->get(route('vouchers.show', $this->vouchers[0]))
                ->assertStatus(418);
    }
}

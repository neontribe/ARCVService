<?php

namespace Tests\Unit\Routes;

use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ServiceRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $vouchers;

    protected function setUp()
    {
        parent::setUp();
        $this->vouchers = factory(\App\Voucher::class, 10)->create();
        $this->users = factory(\App\User::class, 2)->create();
        $this->markets = factory(\App\Market::class, 2)->create();
        $this->traders = factory(\App\Trader::class, 5)->create();
    }

    public function testVouchersIndexRoute()
    {
        $this->get(route('vouchers.index'))
            ->assertStatus(200)
            ->assertJsonStructure([ 0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]])
        ;
    }

    public function testVouchersShowRoute()
    {
        $this->get(route('vouchers.show', $this->vouchers[0]))
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'trader_id', 'code', 'currentstate', 'sponsor_id'])
        ;
    }

    public function testUsersIndexRoute()
    {
        $this->get(route('users.index'))
            ->assertStatus(200)
            ->assertJsonStructure([ 0 => [
                'id', 'name', 'email'
            ]])
        ;
    }

    public function testMarketsIndexRoute()
    {
        $this->get(route('markets.index'))
            ->assertStatus(200)
            ->assertJsonStructure([ 0 => [
                'id', 'name', 'location', 'sponsor_id'
            ]])
        ;
    }

    public function testTradersIndexRoute()
    {
        $this->get(route('traders.index'))
            ->assertStatus(200)
            ->assertJsonStructure([ 0 => [
                'id', 'name', 'pic_url', 'market_id'
            ]])
        ;
    }

    public function testProductionRoutes()
    {
        Config::set('app.url', 'https://voucher-admin.alexandrarose.org.uk');
        $models = ['vouchers', 'users', 'markets', 'traders'];
        foreach ($models as $model) {
            $this->get(route($model . '.index'))
                ->assertStatus(418)
            ;
            $this->get(route($model . '.show', $this->$model[0]))
                ->assertStatus(418)
            ;
        }
    }
}

<?php

namespace Tests\Browser\Pages;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Evaluation;
use App\Market;
use App\Sponsor;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class StoreLogin extends Page
{
    public $sponsor;
    public $market;
    public $centres;
    public $other_sponsor;
    public $other_centres;
    public $centreUser;
    public $pagination_centres;

    public function __construct()
    {
        $this->sponsor = factory(Sponsor::class)->create();
        $eval = new Evaluation([
            "name" => "FamilyHasUnverifiedChildren",
            "value" => 0,
            "purpose" => "notices",
            "entity" => "App\Family",
            "sponsor_id" => $this->sponsor->id
        ]);
        $eval->save();
        $this->sponsor->fresh();

        $this->market = factory(Market::class)->create([
            'sponsor_id' => $this->sponsor->id,
            'name' => 'Test Market',
        ]);

        $this->centres = factory(Centre::class, 5)->create([
            'sponsor_id' =>  $this->sponsor->id
        ]);

        $this->other_sponsor = factory(Sponsor::class)
            ->create();

        $this->other_centres = factory(Centre::class, 3)->create([
            'sponsor_id' =>  $this->other_sponsor->id
        ]);

        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('password_store'),
        ]);
        $this->centreUser->centres()->attach($this->centres[0]->id, ['homeCentre' => true]);

        $this->pagination_centres = factory(Centre::class, 15)->create([
            'sponsor_id' =>  $this->sponsor->id
        ]);
    }
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return 'http://arcv-store.test/login';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs('/login')
                ->assertSee('Email Address')
                ->assertSee('Password')
                ->type('email', $this->centreUser->email)
                ->type('password', "password_store")
                ->press('Log In')
                ->assertPathIs('/dashboard')
                ->assertSee('Main menu')
                ;
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array
     */
    public function elements()
    {
        return [
            '@element' => '#selector',
        ];
    }
}

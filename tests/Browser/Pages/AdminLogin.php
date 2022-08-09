<?php

namespace Tests\Browser\Pages;

use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Market;
use App\Sponsor;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class AdminLogin extends Page
{
    public $sponsor;
    public $market;
    public $adminUser;
    public $centres;
    public $other_sponsor;
    public $other_centres;
    public $centreUser;
    public $pagination_centres;

    public function __construct()
    {
        $this->sponsor = factory(Sponsor::class)->create();
        $this->market = factory(Market::class)->create([
            'sponsor_id' => $this->sponsor->id,
            'name' => 'Test Market',
        ]);

        $this->adminUser =  factory(AdminUser::class)->create([
            "name"  => "test admin user",
            "email" => "testadminuser@example.com",
            "password" => bcrypt('password_admin'),
        ]);

        $this->centres = factory(Centre::class, 5)->create([
            'sponsor_id' =>  $this->sponsor->id
        ]);

        $this->other_sponsor = factory(Sponsor::class)
            ->create();

        $this->other_centres = factory(Centre::class, 3)->create([
            'sponsor_id' =>  $this->other_sponsor->id
        ]);

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create();
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
        return '/login';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url())
                ->assertSee('E-Mail Address')
                ->assertSee('Password')
                ->type('email', $this->adminUser->email)
                ->type('password', "password_admin")
                ->press('Login');
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

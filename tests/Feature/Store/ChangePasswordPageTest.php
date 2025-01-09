<?php

namespace Tests\Feature\Store;

use App\Centre;
use App\CentreUser;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Hash;
use Tests\StoreTestCase;

class ChangePasswordPageTest extends StoreTestCase
{
    use RefreshDatabase;

    public function testItCanResetAPasswordWithAValidLink(): void
    {
        // Invent a Centre for our centreuser
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a token for testing.
        $token = '1fee2254b64595ef575545dbb2b937df4b7d09a5b04c1dd45124a9be13164e44';

        // Create a password reset.
        // NOTE : the token is stored as a hash!
        DB::insert('INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)', [
            $centreUser->email,
            bcrypt($token),
            Carbon::now(),
        ]);

        // Has it saved the original password against the centreuser?
        $this->assertTrue(Hash::check('test_user_pass', $centreUser->password));

        // Se if the page exists.
        $this->visit(route('store.password.reset', [ 'token' => $token ]))
            ->see('Reset Password')
            ->type($centreUser->email, 'email')
            ->type('mynewpassword', 'password')
            ->type('mynewpassword', 'password_confirmation')
            ->press('Reset Password')
            ->seePageIs(route('store.login'))
        ;
        // Load the centreuser again.
        $user2 = CentreUser::find($centreUser->id);

        $this->assertTrue(Hash::check('mynewpassword', $user2->password));
    }

    public function testItCannotResetAPasswordWithAnInvalidLink(): void
    {
        // Invent a Centre for our centreuser
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a token for testing.
        $token = 'abcdef0123456789abcdef0123456789';

        // Create a password reset.
        DB::insert('INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)', [
            $centreUser->email,
            bcrypt($token),
            Carbon::now()->subMinutes(5)
        ]);

        // Has it saved the original password against the centreuser?
        $this->assertTrue(Hash::check('test_user_pass', $centreUser->password));

        $this->actingAs($centreUser)
            ->get(route('store.password.reset', ['token' => 'NotAHashedToken']))
            ->assertResponseStatus(404)
        ;
        // Load the centreuser again.
        $centreUser->fresh();
        $this->assertNotTrue(Hash::check('mynewpassword', $centreUser->password));
    }
}

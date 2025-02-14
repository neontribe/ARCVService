<?php

namespace Tests\Unit\Controllers\Api;

use App\Trader;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApiForgotPasswordControllerTeat extends TestCase
{
    use RefreshDatabase;

    protected Trader $trader;
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        // Create a Trader
        $this->trader = factory(Trader::class)->create();

        // Create a user on that trader
        $this->user = factory(User::class)->create();
        $this->user->traders()->sync([$this->trader->id]);
    }

    public function testItDoesNotPermitUserEnumerationByEmailStuffing(): void
    {
        Mail::fake();

        $data = [
            'valid' => ['email' => $this->user->email],
            'invalid' => ['email' => 'invalid@exampe.com'],
        ];

        // check the values are the same in the files
        $this->assertEquals(trans('passwords.sent'), trans('passwords.user'));

        $route = route('api.user.lost_password');

        // check the response is the same for valid and invalid emails
        foreach ($data as $resetBody) {
            $this->actingAs($this->user, 'api')
                ->json('POST', $route, $resetBody)
                ->assertStatus(200)
                ->assertSeeText(trans('passwords.sent'), false);
        }
    }
}

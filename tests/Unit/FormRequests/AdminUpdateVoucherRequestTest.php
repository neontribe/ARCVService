<?php

namespace Tests\Unit\FormRequests;

use App\Http\Requests\AdminUpdateVoucherRequest;
use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminUpdateVoucherRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $sponsor = factory(Sponsor::class)->create(['shortcode' => 'TST']);
        $errSponsor = factory(Sponsor::class)->create(['shortcode' => 'ERR']);
        $user = factory(User::class)->create();

        Auth::login($user);

        // Create valid vouchers for the test sponsor
        foreach (['TST0101', 'TST0102', 'TST0103', 'TST0104', 'TST0105'] as $code) {
            factory(Voucher::class)->state('printed')->create([
                'code' => $code,
                'sponsor_id' => $sponsor->id,
            ]);
        }

        // Create an error voucher
        factory(Voucher::class)->state('printed')->create([
            'code' => 'ERR0105',
            'sponsor_id' => $errSponsor->id,
        ]);

        Auth::logout();
        $this->rules = (new AdminUpdateVoucherRequest())->rules();
    }

    private function validate(array $data): bool
    {
        return Validator::make($data, $this->rules)->passes();
    }

    /**
     * @dataProvider validationCases
     */
    public function testItValidatesVoucherUpdateRequests(bool $expected, array $data): void
    {
        $this->assertEquals($expected, $this->validate($data));
    }

    public static function validationCases(): array
    {
        return [
            'Valid void transition' => [true, [
                'transition' => 'void',
                'voucher-start' => 'TST0101',
                'voucher-end' => 'TST0105',
            ]],
            'Valid expire transition' => [true, [
                'transition' => 'expire',
                'voucher-start' => 'TST0101',
                'voucher-end' => 'TST0105',
            ]],
            'Missing transition' => [false, [
                'voucher-start' => 'TST0101',
                'voucher-end' => 'TST0105',
            ]],
            'Transition not a string' => [false, [
                'transition' => 1,
                'voucher-start' => 'TST0101',
                'voucher-end' => 'TST0105',
            ]],
            'Missing voucher start' => [false, [
                'transition' => 'expire',
                'voucher-end' => 'TST0105',
            ]],
            'Missing voucher end' => [false, [
                'transition' => 'expire',
                'voucher-start' => 'TST0101',
            ]],
            'Voucher start larger than voucher end' => [false, [
                'transition' => 'expire',
                'voucher-start' => 'TST0105',
                'voucher-end' => 'TST0101',
            ]],
            'Invalid voucher start' => [false, [
                'transition' => 'expire',
                'voucher-start' => 'TST0100',
                'voucher-end' => 'TST0105',
            ]],
            'Invalid voucher end' => [false, [
                'transition' => 'expire',
                'voucher-start' => 'TST0101',
                'voucher-end' => 'TST0106',
            ]],
            'Different voucher prefixes' => [false, [
                'transition' => 'expire',
                'voucher-start' => 'TST0101',
                'voucher-end' => 'ERR0105',
            ]],
        ];
    }
}

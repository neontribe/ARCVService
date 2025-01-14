<?php

namespace Tests\Unit\FormRequests;

use App\Voucher;
use App\Http\Requests\VoucherSearchRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminVoucherSearchRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = (new VoucherSearchRequest())->rules();
    }

    private function validate(array $data): bool
    {
        return Validator::make($data, $this->rules)->passes();
    }

    public function testAllowsSearchWithValidVoucherCode(): void
    {
        $voucher = factory(Voucher::class)->state('dispatched')->create();
        $this->assertTrue($this->validate(['voucher_code' => $voucher->code]));
    }

    public function testRejectsSearchWithInvalidVoucherCode(): void
    {
        $this->assertFalse($this->validate(['voucher_code' => 'InvalidCode']));
    }
}

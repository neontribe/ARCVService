<?php

namespace Tests\Feature;

use App\AdminUser;
use App\Voucher;
use App\Http\Requests\VoucherSearchRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\Validator;
use Tests\StoreTestCase;

class AdminVoucherSearchRequestTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var Validator */
    private $validator;

    /**
     * General validator
     * @param $mockedRequestData
     * @param $rules
     * @return mixed
     */
    protected function validate($mockedRequestData, $rules)
    {
        $validator = app()->get('validator');
        return $validator
            ->make($mockedRequestData, $rules)
            ->passes();
    }

    /**
     * @test
     */
    public function testICanSubmitASearchWithValidCode()
    {
        $voucherToSearch = factory(Voucher::class, 'dispatched')->create();
        $rules = (new VoucherSearchRequest())->rules();
        $mockedRequestData = ['voucher_code' => $voucherToSearch->code];

        $this->assertEquals(
            true,
            $this->validate($mockedRequestData, $rules)
        );
    }

    /**
     * @test
     */
    public function testICannotSubmitASearchWithInvalidValues()
    {
        $rules = (new VoucherSearchRequest())->rules();
        $mockedRequestData = ['voucher_code' => 'This is not a voucher code'];

        $this->assertEquals(
            false,
            $this->validate($mockedRequestData, $rules)
        );
    }
}

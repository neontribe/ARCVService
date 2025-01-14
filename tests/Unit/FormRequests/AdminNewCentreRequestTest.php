<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\Http\Requests\AdminNewCentreRequest;
use App\Sponsor;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;

class AdminNewCentreRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new AdminNewCentreRequest())->rules();
        factory(Centre::class)->create(['prefix' => 'EXISTS']);
        factory(Sponsor::class)->create();
    }

    private function validate(array $mockedRequestData): bool
    {
        return Validator::make($mockedRequestData, $this->rules)->passes();
    }

    /**
     * @dataProvider validationCases
     */
    public function testItValidatesCentreRequests(bool $shouldPass, array $mockedRequestData): void
    {
        $this->assertEquals($shouldPass, $this->validate($mockedRequestData));
    }

    public static function validationCases(): Generator
    {
        yield 'Valid request' => [true, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Missing name' => [false, [
            'sponsor' => 1,
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Name is not a string' => [false, [
            'name' => 1,
            'sponsor' => 1,
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Missing sponsor' => [false, [
            'name' => 'Test Centre',
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Sponsor is not an integer' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 'not an integer',
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Invalid sponsor' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 999,
            'rvid_prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'Missing RVID prefix' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'print_pref' => 'individual',
        ]];

        yield 'RVID is not a string' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 1,
            'print_pref' => 'individual',
        ]];

        yield 'RVID is less than one character' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => '',
            'print_pref' => 'individual',
        ]];

        yield 'RVID is more than five characters' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 'ABCDEF',
            'print_pref' => 'individual',
        ]];

        yield 'RVID already exists' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 'EXISTS',
            'print_pref' => 'not even slightly a print pref',
        ]];

        yield 'Missing print preference' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 'ABCDEF',
        ]];

        yield 'Invalid print preference' => [false, [
            'name' => 'Test Centre',
            'sponsor' => 1,
            'rvid_prefix' => 'ABCDEF',
            'print_pref' => 'not even slightly a print pref',
        ]];
    }
}

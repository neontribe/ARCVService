<?php

namespace Tests\Unit\FormRequests;

use App\Centre;
use App\Http\Requests\AdminNewCentreRequest;
use App\Sponsor;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\StoreTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminNewCentreRequestTest extends StoreTestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new AdminNewCentreRequest())->rules();
        factory(Centre::class)->create(['name' => 'EXIST', 'prefix' => 'EXIST']);
        factory(Sponsor::class)->create();
    }

    private function validate(array $mockedRequestData): bool
    {
        return Validator::make($mockedRequestData, $this->rules)->passes();
    }

    #[DataProvider('validationCases')]
    public function testItValidatesCentreRequests(bool $shouldPass, array $mockedRequestData): void
    {
        $this->assertEquals($shouldPass, $this->validate($mockedRequestData));
    }

    public static function validationCases(): Generator
    {
        yield 'Valid request' => [true, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Missing name' => [false, [
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Name is not a string' => [false, [
            'name' => 1,
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Name already exists' => [false, [
            'name' => 'EXIST',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Missing sponsor' => [false, [
            'name' => 'Test Centre',
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Sponsor is not an integer' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 'not an integer',
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Invalid sponsor' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 999,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Missing RVID prefix' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'RVID is not a string' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 1,
            'print_pref' => 'individual',
        ]];

        yield 'RVID is less than one character' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => '',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'RVID is more than five characters' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'ABCDEF',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'RVID already exists' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'EXIST',
            'print_pref' => 'individual',
            'can_collect' => false,
        ]];

        yield 'Missing print preference' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'ABCDEF',
            'can_collect' => false,
        ]];

        yield 'Invalid print preference' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'ABCDEF',
            'print_pref' => 'not even slightly a print pref',
            'can_collect' => false,
        ]];

        yield 'can_collect can be true' => [true, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => true,
        ]];

        yield 'can_collect might be absent' => [true, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
        ]];

        yield 'can_collect might be null' => [true, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => null,
        ]];

        yield 'can_collect must be boolean' => [false, [
            'name' => 'Test Centre',
            'sponsor_id' => 1,
            'prefix' => 'TSTCT',
            'print_pref' => 'individual',
            'can_collect' => 'true',
        ]];
    }
}

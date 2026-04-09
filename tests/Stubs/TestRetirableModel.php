<?php

namespace Tests\Stubs;

use App\Traits\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Minimal stub model for testing the Retirable trait in isolation.
 *
 * Mirrors the same nullability constraints as a typical user table:
 * name, email, and password are non-nullable; remember_token is nullable.
 */
class TestRetirableModel extends Model
{
    use SoftDeletes;
    use Retirable;

    protected $table = 'retirable_stubs';

    protected $fillable = ['name', 'email', 'password', 'remember_token'];

    protected function retirableFields(): array
    {
        return [
            'name'           => 'retired',
            'email'          => 'retired_' . Str::uuid() . '@retired.invalid',
            'password'       => Hash::make(Str::uuid()),
            'remember_token' => null,
        ];
    }
}

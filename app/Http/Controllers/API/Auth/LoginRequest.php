<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\ApiRequest;

// I feel like we could be using Laravels gates and built in validation.

class LoginRequest extends ApiRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // These should really be rules on the User::class.
        return [
            'username' => 'required|email',
            'password' => 'required',
        ];
    }
}

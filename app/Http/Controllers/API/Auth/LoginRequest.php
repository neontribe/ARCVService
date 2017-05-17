<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\ApiRequest;

class LoginRequest extends ApiRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'    => 'required|email',
            'password' => 'required'
        ];
    }
}

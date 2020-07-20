<?php

namespace App\Http;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        return $validator->errors();
    }

    protected function failedAuthorization()
    {
        return response('403');
    }
}

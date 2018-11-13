<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateSessionRequest extends FormRequest
{
     // NOTE: Can define authorize() if route group auth:store not sufficient.

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        $rules = [
            'centre' => 'integer|exists:centres,id'
        ];

        return $rules;
    }
}

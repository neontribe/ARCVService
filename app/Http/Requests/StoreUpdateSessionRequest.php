<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Route group ath:store should be sufficient guard.
        return true;
    }

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

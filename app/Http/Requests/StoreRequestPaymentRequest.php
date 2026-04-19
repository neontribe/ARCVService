<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): true
    {
        // TODO : determine of existing registration route protection is sufficient.
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        return [
            // Mutually dependent
            'collected_on' => 'required_with_all:collected_at,collected_by|date_format:Y-m-d',
            'collected_at' => 'integer|required_with_all:collected_on,collected_by|exists:centres,id',
            'collected_by' => 'integer|required_with_all:collected_at,collected_on|exists:carers,id'
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // TODO : determine of existing registration route protection is sufficient.
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
            // MAY be one, can be null, members must be distinct.
            'vouchers.*' => 'nullable|distinct|string',
            'collected_on' => 'required_if:collected_at,collected_by|date_format:d-m-Y',
            'collected_at' => 'required_if:collected_on,collected_by|exists:centres',
            'collected_by' => 'required_if:collected_at,collected_on|exists:carers'
        ];

        return $rules;
    }
}

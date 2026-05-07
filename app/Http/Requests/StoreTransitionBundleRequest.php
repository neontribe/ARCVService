<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransitionBundleRequest extends FormRequest
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
            'collected_on' => 'required|date_format:Y-m-d',
            'collected_at' => 'required|integer|exists:centres,id',
            'collected_by' => 'required|exists:carers,id',
            'trader_id' => 'required|integer|exists:traders,id'
        ];
    }
}

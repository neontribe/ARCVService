<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminNewUpdateMarketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Covered by Admin user auth
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // MUST be present, string
            'name' => 'required|string',
            // MUST be present, integer, in table
            'sponsor' => 'required|integer|exists:sponsors,id',
            // MUST be present, string, 160 max length
            'payment_pending' => 'required|string|max:160',
        ];
    }
}

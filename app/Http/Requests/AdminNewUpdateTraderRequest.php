<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminNewUpdateTraderRequest extends FormRequest
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
            'name' => 'required|string|max:160',
            // MUST be present, integer, in table
            'market' => 'required|integer|exists:markets,id',
            // MAY be present, min 1
            'users' => 'array|min:1',
            // MUST be present if email is, string, 160
            'users.*.name' => 'required_with:users.*.email|string|max:160',
            // MUST be present if name is, email, and distinct
            'users.*.email' => 'required_with:users.*.name|email|distinct',
        ];
    }
}

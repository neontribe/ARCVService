<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexCentreUsersRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'orderBy' => 'in:name,homeCentreArea,homeCentre',
            'direction' => 'in:asc,desc',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Get the ID off the route
        $id = $this->route('id') ?? null;
        $market_id = $this->only('market');

        return [
            // MUST be present, string, and not a duplicate of another name in same market.
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('traders', 'name')
                    ->ignore($id)
                    ->where(function ($q) use ($market_id) {
                        return $q->where('market_id', $market_id);
                    })
            ],
            // MUST be present, integer, in table
            'market' => 'required|integer|exists:markets,id',
            // Optional, can be null, can be [yes,on,1,true]
            'disabled' => 'nullable|in:yes,on,1,true',
            // MAY be present, min 1
            'users' => 'array|min:1',
            // MUST be present if email is, string, 160
            'users.*.name' => 'required_with:users.*.email|string|max:160',
            // MUST be present if name is, email, and distinct
            'users.*.email' => 'required_with:users.*.name|email|distinct',
        ];
    }
}

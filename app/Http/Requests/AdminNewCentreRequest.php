<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\NotExistsRule;

class AdminNewCentreRequest extends FormRequest
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
            // MUST be present, string, 1-5 characters and not in use
            'rvid_prefix' => [
                'required',
                'string',
                'between:1,5',
                new NotExistsRule('centres', 'prefix'),
            ],
            // MUST be present, in print_prefs
            'print_pref' => [
                'required',
                Rule::in(config('arc.print_preferences'))
            ]
        ];
    }

    /**
     * Prep input for validation
     */
    public function prepareForValidation()
    {
        if ($this->has('rvid_prefix')) {
            $this->merge(
                // In this system, we're want it uppercase
                ['rvid_prefix' => strtoupper($this->input('rvid_prefix'))]
            );
        }
    }
}

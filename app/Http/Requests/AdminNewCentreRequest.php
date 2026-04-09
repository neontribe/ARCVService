<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminNewCentreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Covered by Admin user auth
        return true;
    }

    /**
     * Normalise input before validation runs.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'can_collect' => $this->boolean('can_collect'),
            'prefix' => strtoupper((string)$this->input('prefix', '')),
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:centres,name'],
            'sponsor_id' => ['required', 'exists:sponsors,id'],
            'prefix' => [
                'required',
                'string',
                'between:1,5',
                'unique:centres,prefix',
            ],
            'print_pref' => [
                'required',
                Rule::in(config('arc.print_preferences')),
            ],
            'can_collect' => ['nullable', 'boolean']
        ];
    }

    /**
     * Human-readable attribute names used in error messages.
     */
    public function attributes(): array
    {
        return [
            'sponsor_id' => 'sponsor',
            'prefix' => 'RVID prefix',
            'print_pref' => 'print preference',
            'can-collect' => 'collection',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'That name is already in use.',
            'prefix.unique' => 'That RVID prefix is already in use.',
            'prefix.between' => 'The RVID prefix must be between 1 and 5 characters.',
            'print_pref.in' => 'The selected print preference is not valid.',
        ];
    }
}

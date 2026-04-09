<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateCentreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
        $centreId = $this->route('centre')?->id;
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('centres', 'name')->ignore($centreId),
            ],
            'sponsor_id' => ['required', 'exists:sponsors,id'],
            'prefix' => [
                'required',
                'string',
                'between:1,5',
                Rule::unique('centres', 'prefix')->ignore($centreId),
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

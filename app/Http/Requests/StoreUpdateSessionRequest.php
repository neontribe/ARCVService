<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Route group auth:store should be sufficient guard.
        return true;
    }

    public function rules(): array
    {
        return [
            'centre' => [
                // must exist
                'required',
                Rule::when(
                    // if it's NOT "all" then...
                    $this->input('centre') !== 'all',
                    // is this an integer that exists in the database?
                    ['integer', 'exists:centres,id']
                ),
            ],
        ];
    }
}

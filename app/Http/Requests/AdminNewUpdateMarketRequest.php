<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Get the ID off the route
        $id = $this->route('id') ?? null;
        // Get the sponsor out of the data
        $sponsor_id = $this->input('sponsor') ?? null;

        return [
            // MUST be present, string, and not a duplicate of another name in same sponsor.
            'name' => [
                'required',
                'string',
                Rule::unique('markets', 'name')
                    ->ignore($id)
                    ->where(function ($q) use ($sponsor_id) {
                        return $q->where('sponsor_id', $sponsor_id);
                    })
            ],
            // MUST be present, integer, in table
            'sponsor' => 'required|integer|exists:sponsors,id',
            // MUST be present, string, 160 max length
            'payment_message' => 'required|string|max:160',
        ];
    }
}

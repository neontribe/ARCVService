<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Route group ath:store should be sufficient guard.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        /*
         * A bit hacky but when we send 'all' to the SessionController we want to blank
         * the session value for CentreUserCurrentCentreId and the role below was blocking
         * this as it only permits existing centre ids
         */
        $input = $this->all(['centre']);
        if ($input["centre"] === "all") {
            return [];
        }

        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        return [
            'centre' => 'integer|exists:centres,id'
        ];
    }
}

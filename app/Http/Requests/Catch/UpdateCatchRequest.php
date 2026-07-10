<?php

namespace App\Http\Requests\Catch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A catch is placed either at a known lake or at a free-form spot,
            // so neither field can be required on its own.
            'lake_id' => ['sometimes', 'nullable', 'exists:lakes,id'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fish_name' => ['sometimes', 'required', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'caught_at' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

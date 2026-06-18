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
            'lake_id' => ['sometimes', 'required', 'exists:lakes,id'],
            'fish_name' => ['sometimes', 'required', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'caught_at' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

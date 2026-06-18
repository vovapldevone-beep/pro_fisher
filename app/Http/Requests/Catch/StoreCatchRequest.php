<?php

namespace App\Http\Requests\Catch;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lake_id' => ['required', 'exists:lakes,id'],
            'fish_name' => ['required', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'caught_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

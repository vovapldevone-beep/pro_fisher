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
        $isCatch = $this->input('type', 'catch') === 'catch';

        return [
            'type'      => ['nullable', 'string', 'in:catch,post'],
            'lake_id'   => [$isCatch ? 'required' : 'nullable', 'exists:lakes,id'],
            'fish_name' => [$isCatch ? 'required' : 'nullable', 'string', 'max:255'],
            'weight'    => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'photo'     => ['nullable', 'image', 'max:5120'],
            'caught_at' => [$isCatch ? 'required' : 'nullable', 'date'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'location'  => ['nullable', 'string', 'max:255'],
        ];
    }
}

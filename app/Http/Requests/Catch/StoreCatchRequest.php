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
            // A catch needs a place, but the user may either pick a known lake
            // or type their own spot — one of the two, not necessarily both.
            'lake_id'   => [$isCatch ? 'required_without:location' : 'nullable', 'nullable', 'exists:lakes,id'],
            'location'  => [$isCatch ? 'required_without:lake_id' : 'nullable', 'nullable', 'string', 'max:255'],
            'fish_name' => [$isCatch ? 'required' : 'nullable', 'string', 'max:255'],
            'weight'    => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'photo'     => ['nullable', 'image', 'max:5120'],
            'caught_at' => [$isCatch ? 'required' : 'nullable', 'date'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ];
    }
}

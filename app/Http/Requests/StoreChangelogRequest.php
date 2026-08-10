<?php

namespace App\Http\Requests;

class StoreChangelogRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,solved'],
            'issues' => ['nullable', 'array'],
            'issues.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'status.in' => 'Status harus pending atau solved.',
        ];
    }
}

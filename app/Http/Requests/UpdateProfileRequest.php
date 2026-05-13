<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nama lengkap user.',
                'example' => 'Budi Santoso',
            ],
            'email' => [
                'description' => 'Email unik user.',
                'example' => 'budi@example.com',
            ],
        ];
    }
}

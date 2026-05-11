<?php

namespace App\Http\Requests;

class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email user.',
                'example' => 'user@example.com',
            ],
            'password' => [
                'description' => 'Password user.',
                'example' => 'password123',
            ],
        ];
    }
}

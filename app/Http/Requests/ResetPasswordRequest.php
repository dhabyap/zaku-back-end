<?php

namespace App\Http\Requests;

class ResetPasswordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email user yang meminta reset password.',
                'example' => 'user@example.com',
            ],
            'token' => [
                'description' => 'Token reset password dari email atau log.',
                'example' => 'reset-token',
            ],
            'password' => [
                'description' => 'Password baru minimal 8 karakter.',
                'example' => 'password123',
            ],
            'password_confirmation' => [
                'description' => 'Konfirmasi password baru.',
                'example' => 'password123',
            ],
        ];
    }
}

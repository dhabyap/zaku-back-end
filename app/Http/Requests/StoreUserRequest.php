<?php

namespace App\Http\Requests;

class StoreUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'name' => ['required_without:full_name', 'string', 'max:255'],
            'full_name' => ['required_without:name', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'The email address is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email user yang unik.',
                'example' => 'user@example.com',
            ],
            'password' => [
                'description' => 'Password minimal 8 karakter.',
                'example' => 'password123',
            ],
            'full_name' => [
                'description' => 'Nama lengkap user.',
                'example' => 'John Doe',
            ],
            'phone_number' => [
                'description' => 'Nomor telepon opsional.',
                'example' => '08123456789',
            ],
        ];
    }
}

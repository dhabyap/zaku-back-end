<?php

namespace App\Http\Requests;

class VerifyEmailRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email user yang akan diverifikasi.',
                'example' => 'user@example.com',
            ],
            'code' => [
                'description' => 'Kode verifikasi 6 digit dari email atau log.',
                'example' => '123456',
            ],
        ];
    }
}

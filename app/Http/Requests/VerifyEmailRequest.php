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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'user_id' => [
                'description' => 'ID user yang akan diverifikasi.',
                'example' => 1,
            ],
            'code' => [
                'description' => 'Kode verifikasi 6 digit dari email atau log.',
                'example' => '123456',
            ],
        ];
    }
}

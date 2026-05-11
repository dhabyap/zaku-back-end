<?php

namespace App\Http\Requests;

class EmailRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email user.',
                'example' => 'user@example.com',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SendMoneyRequest extends WalletAmountRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'recipient_email' => [
                'required',
                'email',
                Rule::exists('users', 'email'),
                Rule::notIn([$this->user()?->email]),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return [
            'recipient_email.not_in' => 'You cannot send money to yourself.',
        ];
    }

    public function bodyParameters(): array
    {
        return array_merge(parent::bodyParameters(), [
            'recipient_email' => [
                'description' => 'Email penerima yang sudah terdaftar.',
                'example' => 'user@email.com',
            ],
            'note' => [
                'description' => 'Catatan transfer opsional.',
                'example' => 'opsional',
            ],
        ]);
    }
}

<?php

namespace App\Http\Requests;

class WithdrawRequest extends WalletAmountRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'account_number' => ['required', 'string', 'max:50'],
        ]);
    }

    public function bodyParameters(): array
    {
        return array_merge(parent::bodyParameters(), [
            'account_number' => [
                'description' => 'Nomor rekening tujuan penarikan.',
                'example' => '1234567890',
            ],
        ]);
    }
}

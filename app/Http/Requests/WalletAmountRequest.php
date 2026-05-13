<?php

namespace App\Http\Requests;

class WalletAmountRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => [
                'description' => 'Nominal transaksi wallet dalam integer IDR.',
                'example' => 100000,
            ],
        ];
    }
}

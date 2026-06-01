<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class TransactionRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('category')) {
            $this->merge([
                'category' => strtoupper((string) $this->input('category')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'integer', 'gt:0'],
            'description' => ['required', 'string', 'max:1000'],
            'category' => ['required', 'string', 'exists:categories,name'],
            'transaction_date' => ['sometimes', 'date'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => [
                'description' => 'Jenis transaksi.',
                'example' => 'expense',
            ],
            'amount' => [
                'description' => 'Nominal transaksi.',
                'example' => 65000,
            ],
            'description' => [
                'description' => 'Deskripsi transaksi.',
                'example' => 'Beli kopi',
            ],
            'category' => [
                'description' => 'Nama kategori transaksi.',
                'example' => 'MAKANAN',
            ],
            'transaction_date' => [
                'description' => 'Tanggal transaksi.',
                'example' => '2026-05-20',
            ],
        ];
    }
}

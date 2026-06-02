<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends ApiFormRequest
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
            'type' => ['sometimes', Rule::in(['income', 'expense'])],
            'amount' => ['sometimes', 'integer', 'gt:0'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'category' => ['sometimes', 'string', 'exists:categories,name'],
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
                'example' => 50000,
            ],
            'description' => [
                'description' => 'Deskripsi transaksi.',
                'example' => 'Makan siang',
            ],
            'category' => [
                'description' => 'Nama kategori transaksi.',
                'example' => 'MAKANAN',
            ],
            'transaction_date' => [
                'description' => 'Tanggal transaksi.',
                'example' => '2026-06-01',
            ],
        ];
    }
}

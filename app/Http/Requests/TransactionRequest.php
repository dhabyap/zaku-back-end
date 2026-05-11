<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class TransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['debit', 'credit'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(['pending', 'completed', 'failed'])],
            'transaction_date' => ['sometimes', 'date'],
            'reference_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => [
                'description' => 'Jenis transaksi.',
                'example' => 'credit',
            ],
            'amount' => [
                'description' => 'Nominal transaksi.',
                'example' => 100000,
            ],
            'description' => [
                'description' => 'Deskripsi transaksi.',
                'example' => 'Top up wallet',
            ],
            'status' => [
                'description' => 'Status transaksi.',
                'example' => 'pending',
            ],
            'transaction_date' => [
                'description' => 'Tanggal transaksi.',
                'example' => '2026-05-11 10:00:00',
            ],
            'reference_id' => [
                'description' => 'ID referensi eksternal opsional.',
                'example' => 'TRX-20260511-0001',
            ],
        ];
    }
}

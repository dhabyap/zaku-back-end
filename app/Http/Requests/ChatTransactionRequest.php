<?php

namespace App\Http\Requests;

class ChatTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'message' => [
                'description' => 'Input teks natural language untuk dicatat sebagai transaksi.',
                'example' => 'Beli kopi di Starbucks 65 ribu',
            ],
        ];
    }
}

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
}

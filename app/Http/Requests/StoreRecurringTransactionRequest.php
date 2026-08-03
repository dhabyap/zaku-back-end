<?php

namespace App\Http\Requests;

use App\Models\RecurringTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([\App\Models\Transaction::TYPE_INCOME, \App\Models\Transaction::TYPE_EXPENSE])],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'frequency' => ['required', Rule::in(RecurringTransaction::FREQUENCIES)],
            'interval_value' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}

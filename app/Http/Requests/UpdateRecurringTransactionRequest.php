<?php

namespace App\Http\Requests;

use App\Models\RecurringTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [Rule::in([\App\Models\Transaction::TYPE_INCOME, \App\Models\Transaction::TYPE_EXPENSE])],
            'amount_cents' => ['integer', 'min:1'],
            'description' => ['string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'frequency' => [Rule::in(RecurringTransaction::FREQUENCIES)],
            'interval_value' => ['integer', 'min:1', 'max:365'],
            'start_date' => ['date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => [Rule::in([RecurringTransaction::STATUS_ACTIVE, RecurringTransaction::STATUS_PAUSED, RecurringTransaction::STATUS_CANCELLED])],
        ];
    }
}

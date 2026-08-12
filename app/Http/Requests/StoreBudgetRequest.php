<?php

namespace App\Http\Requests;

use App\Models\Budget;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'exists:categories,name'],
            'amount' => ['required', 'integer', 'gt:0'],
            'period' => ['required', Rule::in(Budget::VALID_PERIODS)],
            'start_date' => ['sometimes', 'date'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'category' => [
                'description' => 'Nama kategori budget.',
                'example' => 'MAKANAN',
            ],
            'amount' => [
                'description' => 'Nominal budget dalam integer IDR.',
                'example' => 500000,
            ],
            'period' => [
                'description' => 'Periode budget: daily, weekly, atau monthly.',
                'example' => 'monthly',
            ],
            'start_date' => [
                'description' => 'Tanggal mulai budget (default: hari ini).',
                'example' => '2026-08-01',
            ],
        ];
    }
}

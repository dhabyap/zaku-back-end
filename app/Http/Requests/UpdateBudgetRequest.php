<?php

namespace App\Http\Requests;

class UpdateBudgetRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monthly_budget' => ['required', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'monthly_budget' => [
                'description' => 'Budget bulanan dalam integer IDR.',
                'example' => 3000000,
            ],
        ];
    }
}

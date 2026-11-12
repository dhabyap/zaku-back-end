<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingStep2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monthly_budget' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'theme_preference' => ['nullable', 'in:light,dark,system'],
        ];
    }

    public function messages(): array
    {
        return [
            'monthly_budget.required' => 'Anggaran bulanan wajib diisi',
            'monthly_budget.numeric' => 'Anggaran bulanan harus berupa angka',
            'monthly_budget.min' => 'Anggaran bulanan tidak boleh negatif',
        ];
    }
}
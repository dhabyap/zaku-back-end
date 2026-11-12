<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['nullable', 'string', 'max:100'],
            'category_type' => ['nullable', 'in:income,expense'],
            'category_icon' => ['nullable', 'string', 'max:10'],
            'category_color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'initial_balance' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_color.regex' => 'Format warna tidak valid (gunakan format hex #RRGGBB)',
            'initial_balance.numeric' => 'Saldo awal harus berupa angka',
        ];
    }
}
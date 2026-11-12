<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255', 'min:2'],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]{8,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi',
            'full_name.min' => 'Nama lengkap minimal 2 karakter',
            'phone_number.regex' => 'Format nomor telepon tidak valid',
        ];
    }
}
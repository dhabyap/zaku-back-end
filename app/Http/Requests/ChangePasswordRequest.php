<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8), 'confirmed', 'different:old_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.different' => 'The new password must be different from the old password.',
        ];
    }
}

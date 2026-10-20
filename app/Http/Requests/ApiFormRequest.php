<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'status' => 'error',
            'data' => [
                'errors' => $validator->errors(),
            ],
            'message' => 'Ada data yang belum sesuai. Periksa bagian yang ditandai, lalu coba lagi.',
        ], 422));
    }
}

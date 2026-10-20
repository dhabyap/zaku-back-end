<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(mixed $data = null, string $message = 'Permintaan berhasil', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'status' => 'success',
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(string $message = 'Permintaan tidak dapat diproses. Coba sebentar lagi.', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'status' => 'error',
            'data' => $errors === null ? null : ['errors' => $errors],
            'message' => $message,
        ];

        return response()->json($response, $statusCode);
    }

    protected function validationErrorResponse(mixed $errors): JsonResponse
    {
        return $this->errorResponse('Ada data yang belum sesuai. Periksa bagian yang ditandai, lalu coba lagi.', 422, $errors);
    }

    protected function unauthorizedResponse(string $message = 'Silakan masuk terlebih dahulu.'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse(string $message = 'Anda tidak memiliki izin untuk ini.'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    protected function notFoundResponse(string $message = 'Data tidak ditemukan atau sudah dihapus.'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }
}

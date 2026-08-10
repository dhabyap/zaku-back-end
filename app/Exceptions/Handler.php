<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render(Request $request, Throwable $e)
    {
        if ($e instanceof ThrottleRequestsException) {
            $retryAfter = $e->getRetryAfter();
            $seconds = is_int($retryAfter) ? $retryAfter : (int) $retryAfter;

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => "Terlalu banyak permintaan. Silakan tunggu {$seconds} detik lagi.",
                'retry_after' => $seconds,
            ], 429);
        }

        return parent::render($request, $e);
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ThrottleRequestsException) {
            $retryAfter = $e->getRetryAfter();
            $seconds = is_int($retryAfter) ? $retryAfter : (int) $retryAfter;

            $retryAt = \Illuminate\Support\Carbon::now()->addSeconds($seconds);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => "Terlalu banyak permintaan. Silakan tunggu {$seconds} detik lagi.",
                'retry_after' => $seconds,
                'retry_at' => $retryAt->toIso8601String(),
                'retry_at_local' => $retryAt->format('Y-m-d H:i:s'),
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Reset' => $retryAt->timestamp,
            ]);
        }

        return parent::render($request, $e);
    }
}

<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthenticate
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $this->unauthorizedResponse('Silakan masuk terlebih dahulu untuk melakukan ini.');
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException) {
            return $this->unauthorizedResponse('Sesi Anda sudah berakhir. Silakan masuk kembali.');
        } catch (TokenInvalidException) {
            return $this->unauthorizedResponse('Sesi tidak dapat digunakan. Silakan masuk kembali.');
        } catch (JWTException) {
            return $this->unauthorizedResponse('Sesi tidak berakses. Silakan masuk kembali.');
        }

        if (! $user) {
            return $this->unauthorizedResponse('Akun diketikan tidak akabilir. Silakan masuk kembali.');
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

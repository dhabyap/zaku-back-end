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
            return $this->unauthorizedResponse('Authorization token is required.');
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException) {
            return $this->unauthorizedResponse('Authorization token has expired.');
        } catch (TokenInvalidException) {
            return $this->unauthorizedResponse('Authorization token is invalid.');
        } catch (JWTException) {
            return $this->unauthorizedResponse('Authorization token could not be processed.');
        }

        if (! $user) {
            return $this->unauthorizedResponse('User could not be authenticated.');
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

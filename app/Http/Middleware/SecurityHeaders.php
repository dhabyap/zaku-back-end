<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add security headers to response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isHttps = $request->isSecure();
        $csp = config('security.csp.enabled', true);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        // HSTS - only in production HTTPS
        if ($isHttps && config('security.hsts.enabled', true)) {
            $maxAge = (int) config('security.hsts.max_age', 31536000);
            $response->headers->set('Strict-Transport-Security', 'max-age=' . $maxAge . '; includeSubDomains');
        }

        // CSP
        if ($csp) {
            $cspHeader = $this->buildCsp($request);
            $response->headers->set('Content-Security-Policy', $cspHeader);
        }

        // Remove X-Powered-By
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Build Content-Security-Policy header.
     */
    private function buildCsp(Request $request): string
    {
        $env = config('app.env', 'production');
        $frontendUrl = rtrim(config('app.frontend_url', ''), '/');

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
            'font-src' => ["'self'", 'https://fonts.gstatic.com', 'data:'],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        // Allow frontend origin for connect-src
        if ($frontendUrl) {
            $directives['connect-src'][] = $frontendUrl;
        }

        // Add Vite dev server origins in development
        if ($env === 'local' || $env === 'development') {
            $directives['connect-src'][] = 'ws://localhost:*';
            $directives['script-src'][] = "'unsafe-inline'";
            $directives['script-src'][] = 'https://vitejs.dev';
            $directives['script-src'][] = 'https://cdn.jsdelivr.net';
        }

        // Build header string
        $parts = [];
        foreach ($directives as $directive => $sources) {
            $parts[] = $directive . ' ' . implode(' ', $sources);
        }

        return implode('; ', $parts);
    }
}
<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;

class TrackActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Skip tracking in testing environment to avoid DB errors
        if (app()->environment('testing')) {
            return $response;
        }

        // Track page view untuk API GET requests
        if ($request->is('api/v1/*') && $request->isMethod('GET')) {
            $page = $this->extractPage($request);
            ActivityLog::logPageView($page, $request->user());
        }

        return $response;
    }

    /**
     * Extract page name dari request path.
     */
    private function extractPage(Request $request): string
    {
        $path = $request->path();

        // Hapus prefix api/v1/
        $path = str_replace('api/v1/', '', $path);

        // Ambil bagian pertama (resource name)
        $parts = explode('/', $path);

        return $parts[0] ?? 'unknown';
    }
}

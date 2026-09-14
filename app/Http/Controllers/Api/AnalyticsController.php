<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * Dashboard analytics (admin only).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return $this->forbiddenResponse('Akses ditolak.');
        }

        $days = (int) $request->query('days', 30);

        return $this->successResponse([
            'users' => [
                'total' => User::count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'transactions' => [
                'total' => Transaction::count(),
                'this_month' => Transaction::where('transaction_date', '>=', now()->startOfMonth()->toDateString())->count(),
            ],
            'page_views' => ActivityLog::getAnalytics('page_view', $days),
            'feature_usage' => ActivityLog::getAnalytics('feature_used', $days),
            'ai_chat_usage' => Transaction::where('source', 'chat')
                ->where('created_at', '>=', now()->subDays($days))
                ->count(),
        ], 'Analytics dashboard');
    }

    /**
     * User activity log.
     */
    public function activity(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->query('per_page', 20));

        return $this->successResponse([
            'items' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 'Activity log');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request, DashboardService $dashboardService): JsonResponse
    {
        return $this->successResponse(
            $dashboardService->getDashboard($request->user()),
            'Dashboard berhasil diambil',
        );
    }
}

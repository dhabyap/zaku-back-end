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

    public function monthlyRecap(Request $request, DashboardService $dashboardService): JsonResponse
    {
        $month = $request->query('month', null);
        $year = $request->query('year', null);

        // Default to previous month if no month/year is provided
        if (empty($month) || empty($year)) {
            $prevMonth = now()->subMonth();
            $month = $prevMonth->month;
            $year = $prevMonth->year;
        }

        return $this->successResponse(
            $dashboardService->getMonthlyRecap($request->user(), (int) $month, (int) $year),
            'Rekapan bulanan berhasil diambil',
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InsightService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InsightController extends Controller
{
    use ApiResponse;

    public function index(Request $request, InsightService $service): JsonResponse
    {
        $user = $request->user();

        $insights = $service->getInsights($user);

        return $this->successResponse($insights, 'Insights retrieved');
    }
}

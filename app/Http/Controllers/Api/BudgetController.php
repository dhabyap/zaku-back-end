<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Services\BudgetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use ApiResponse;

    public function index(Request $request, BudgetService $service): JsonResponse
    {
        $budgets = $service->getUserBudgets($request->user());

        return $this->successResponse($budgets, 'Budgets retrieved');
    }

    public function store(StoreBudgetRequest $request, BudgetService $service): JsonResponse
    {
        try {
            $budget = $service->createBudget($request->user(), $request->validated());

            return $this->successResponse($budget, 'Budget created', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function update(UpdateBudgetRequest $request, int $id, BudgetService $service): JsonResponse
    {
        try {
            $budget = $service->updateBudget($request->user(), $id, $request->validated());

            return $this->successResponse($budget, 'Budget updated');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget not found.');
        }
    }

    public function destroy(int $id, Request $request, BudgetService $service): JsonResponse
    {
        try {
            $service->deleteBudget($request->user(), $id);

            return $this->successResponse(null, 'Budget deleted');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget not found.');
        }
    }

    public function progress(int $id, Request $request, BudgetService $service): JsonResponse
    {
        try {
            $progress = $service->getBudgetProgress($request->user(), $id);

            return $this->successResponse($progress, 'Budget progress retrieved');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget not found.');
        }
    }

    public function allProgress(Request $request, BudgetService $service): JsonResponse
    {
        $budgets = $service->getUserBudgets($request->user());
        $progress = $budgets->map(function ($budget) use ($service, $request) {
            return $service->getBudgetProgress($request->user(), $budget->id);
        });

        return $this->successResponse($progress, 'Budget progress retrieved');
    }
}

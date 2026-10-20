<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\ActivityLog;
use App\Models\Budget;
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

        return $this->successResponse($budgets, 'Daftar batas pengeluaran berhasil dimuat');
    }

    public function store(StoreBudgetRequest $request, BudgetService $service): JsonResponse
    {
        try {
            $budget = $service->createBudget($request->user(), $request->validated());

            ActivityLog::log($budget, 'created', null, $budget->toArray(), null, $request);

            return $this->successResponse($budget, 'Batas pengeluaran berhasil dibuat', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Data budget belum sesuai. Periksa kembali, lalu coba lagi.', 422);
        }
    }

    public function update(UpdateBudgetRequest $request, int $id, BudgetService $service): JsonResponse
    {
        try {
            $budget = $service->updateBudget($request->user(), $id, $request->validated());

            ActivityLog::log($budget, 'updated', $budget->getOriginal(), $budget->fresh()->toArray(), null, $request);

            return $this->successResponse($budget, 'Batas pengeluaran berhasil diperbarui');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget tidak ditemukan atau sudah dihapus.');
        }
    }

    public function destroy(int $id, Request $request, BudgetService $service): JsonResponse
    {
        try {
            $budget = Budget::where('user_id', $request->user()->id)->findOrFail($id);

            $service->deleteBudget($request->user(), $id);

            ActivityLog::log($budget, 'deleted', $budget->toArray(), null, null, $request);

            return $this->successResponse(null, 'Batas pengeluaran berhasil dihapus');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget tidak ditemukan atau sudah dihapus.');
        }
    }

    public function progress(int $id, Request $request, BudgetService $service): JsonResponse
    {
        try {
            $progress = $service->getBudgetProgress($request->user(), $id);

            return $this->successResponse($progress, 'Progres budget berhasil dimuat');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Budget tidak ditemukan atau sudah dihapus.');
        }
    }

    public function allProgress(Request $request, BudgetService $service): JsonResponse
    {
        $budgets = $service->getUserBudgets($request->user());
        $progress = $budgets->map(function ($budget) use ($service, $request) {
            return $service->getBudgetProgress($request->user(), $budget->id);
        });

        return $this->successResponse($progress, 'Progres budget berhasil dimuat');
    }
}


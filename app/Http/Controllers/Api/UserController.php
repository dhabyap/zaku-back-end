<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $budget = (int) $user->monthly_budget;
        $budgetUsed = (int) $this->transactions($user->id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return $this->successResponse([
            'name' => $user->full_name,
            'email' => $user->email,
            'avatar_initial' => strtoupper(substr((string) $user->full_name, 0, 1)),
            'member_status' => 'MEMBER AKTIF',
            'stats' => [
                'total_transactions' => $this->transactions($user->id)->count(),
                'transactions_this_month' => $this->transactions($user->id)
                    ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'largest_transaction_amount' => (int) $this->transactions($user->id)->max('amount'),
                'unique_categories_used' => (int) $this->transactions($user->id)
                    ->whereNotNull('category_id')
                    ->distinct('category_id')
                    ->count('category_id'),
            ],
            'budget' => [
                'monthly_budget' => $budget,
                'budget_used' => $budgetUsed,
                'budget_remaining' => $budget - $budgetUsed,
                'budget_percentage' => $budget > 0 ? (int) round(($budgetUsed / $budget) * 100) : 0,
            ],
        ], 'Profil berhasil diambil');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'full_name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
        ])->save();

        return $this->successResponse([
            'name' => $user->full_name,
            'email' => $user->email,
            'avatar_initial' => strtoupper(substr((string) $user->full_name, 0, 1)),
        ], 'Profil berhasil diperbarui');
    }

    public function updateBudget(UpdateBudgetRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'monthly_budget' => $request->integer('monthly_budget'),
        ])->save();

        return $this->successResponse([
            'monthly_budget' => (int) $user->monthly_budget,
        ], 'Budget berhasil diperbarui');
    }

    private function transactions(int $userId): Builder
    {
        return Transaction::query()
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $userId))
            ->where('status', Transaction::STATUS_COMPLETED);
    }
}

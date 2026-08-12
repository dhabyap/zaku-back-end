<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BudgetService
{
    public function getUserBudgets(User $user): Collection
    {
        return Budget::where('user_id', $user->id)
            ->with('category')
            ->get();
    }

    public function createBudget(User $user, array $data): Budget
    {
        $category = Category::where('name', strtoupper($data['category']))->firstOrFail();

        $existingBudget = Budget::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->where('period', $data['period'])
            ->first();

        if ($existingBudget) {
            throw new \InvalidArgumentException('Budget untuk kategori dan periode ini sudah ada.');
        }

        return Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => $data['amount'],
            'period' => $data['period'],
            'start_date' => $data['start_date'] ?? Carbon::now()->toDateString(),
            'end_date' => $data['end_date'] ?? null,
        ]);
    }

    public function updateBudget(User $user, int $budgetId, array $data): Budget
    {
        $budget = Budget::where('user_id', $user->id)->findOrFail($budgetId);

        if (isset($data['amount'])) {
            $budget->amount = $data['amount'];
        }

        if (isset($data['period'])) {
            $budget->period = $data['period'];
        }

        if (isset($data['start_date'])) {
            $budget->start_date = $data['start_date'];
        }

        if (array_key_exists('end_date', $data)) {
            $budget->end_date = $data['end_date'];
        }

        $budget->save();

        return $budget;
    }

    public function deleteBudget(User $user, int $budgetId): bool
    {
        $budget = Budget::where('user_id', $user->id)->findOrFail($budgetId);

        return $budget->delete();
    }

    public function getBudgetProgress(User $user, int $budgetId): array
    {
        $budget = Budget::where('user_id', $user->id)
            ->with('category')
            ->findOrFail($budgetId);

        $spent = $this->calculateSpent($budget);

        return [
            'budget' => [
                'id' => $budget->id,
                'category' => $budget->category->name,
                'amount' => $budget->amount,
                'period' => $budget->period,
            ],
            'spent' => (int) $spent,
            'remaining' => max(0, $budget->amount - (int) $spent),
            'percentage' => $budget->amount > 0 ? (int) round(($spent / $budget->amount) * 100) : 0,
            'status' => $budget->getStatus($spent),
        ];
    }

    private function calculateSpent(Budget $budget): float
    {
        $wallet = $budget->user->wallet;
        if (! $wallet) {
            return 0;
        }

        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $query = Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $budget->category_id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('status', Transaction::STATUS_COMPLETED);

        if ($budget->period === Budget::PERIOD_DAILY) {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($budget->period === Budget::PERIOD_WEEKLY) {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        }

        $query->whereBetween('transaction_date', [$start, $end]);

        return (float) $query->sum('amount');
    }
}

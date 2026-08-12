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

        try {
            return Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => $data['amount'],
                'period' => $data['period'],
                'start_date' => $data['start_date'] ?? Carbon::now()->toDateString(),
                'end_date' => $data['end_date'] ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new \InvalidArgumentException('Budget untuk kategori dan periode ini sudah ada.', 0, $e);
            }

            throw $e;
        }
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

        // Derive window from budget's own dates
        $start = $budget->start_date ? $budget->start_date->copy()->startOfDay() : $now->copy()->startOfMonth();
        $end = $budget->end_date ? $budget->end_date->copy()->endOfDay() : $this->deriveEndFromStart($start, $budget->period);

        // Clamp: don't count future expenses
        if ($end->isAfter($now)) {
            $end = $now->copy()->endOfDay();
        }

        // If start is in the future, nothing spent yet
        if ($start->isAfter($now)) {
            return 0;
        }

        return (float) Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $budget->category_id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');
    }

    private function deriveEndFromStart(Carbon $start, string $period): Carbon
    {
        return match ($period) {
            Budget::PERIOD_DAILY => $start->copy()->endOfDay(),
            Budget::PERIOD_WEEKLY => $start->copy()->endOfWeek(),
            Budget::PERIOD_MONTHLY => $start->copy()->endOfMonth(),
            default => $start->copy()->endOfMonth(),
        };
    }
}

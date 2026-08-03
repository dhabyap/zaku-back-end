<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function getDashboard(User $user): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $monthlyQuery = $this->completedTransactions($user)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalIncome = (int) (clone $monthlyQuery)->where('type', Transaction::TYPE_INCOME)->sum('amount');
        $totalExpense = (int) (clone $monthlyQuery)->where('type', Transaction::TYPE_EXPENSE)->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;
        $monthlyBudget = (int) $user->monthly_budget;
        $budgetUsedPercentage = $this->budgetUsedPercentage($monthlyBudget, $totalExpense);
        $expenseByCategory = $this->expenseByCategory($user, $start, $end, $totalExpense);

        return [
            'current_month_balance' => $netCashflow,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cashflow' => $netCashflow,
            'monthly_budget' => $monthlyBudget,
            'budget_remaining' => $monthlyBudget - $totalExpense,
            'budget_used_percentage' => $budgetUsedPercentage,
            'budget_status' => $this->budgetStatus($monthlyBudget, $budgetUsedPercentage),
            'top_spending_category' => $this->topSpendingCategory($expenseByCategory),
            'insight_strip' => $this->buildInsight($user),
            'recent_transactions' => $this->recentTransactions($user),
            'expense_by_category' => $expenseByCategory,
        ];
    }

    private function completedTransactions(User $user): Builder
    {
        return Transaction::query()
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $user->id))
            ->where('status', Transaction::STATUS_COMPLETED);
    }

    private function recentTransactions(User $user): array
    {
        $query = $this->completedTransactions($user)
            ->latest('transaction_date');

        if ($this->hasCategoryTable()) {
            $query->with('category');
        }

        return $query
            ->limit(5)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'amount' => (int) $transaction->amount,
                'type' => $transaction->type,
                'category_name' => $transaction->category?->name ?? 'LAINNYA',
                'category_icon' => $transaction->category?->icon ?? '📌',
                'date_formatted' => DateLabelService::date($transaction->transaction_date),
            ])
            ->all();
    }

    private function expenseByCategory(User $user, Carbon $start, Carbon $end, int $totalExpense): array
    {
        if ($totalExpense <= 0) {
            return [];
        }

        $query = $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$start, $end]);

        if ($this->hasCategoryTable()) {
            $query->with('category');
        }

        return $query
            ->get()
            ->groupBy(fn (Transaction $transaction) => $transaction->category?->name ?? 'LAINNYA')
            ->map(function ($transactions) use ($totalExpense) {
                $first = $transactions->first();
                $amount = (int) $transactions->sum('amount');

                return [
                    'category_name' => $first->category?->name ?? 'LAINNYA',
                    'category_icon' => $first->category?->icon ?? '📌',
                    'amount' => $amount,
                    'percentage_of_expense' => (int) round(($amount / $totalExpense) * 100),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    private function budgetUsedPercentage(int $monthlyBudget, int $totalExpense): int
    {
        if ($monthlyBudget <= 0) {
            return 0;
        }

        return (int) round(($totalExpense / $monthlyBudget) * 100);
    }

    private function budgetStatus(int $monthlyBudget, int $budgetUsedPercentage): string
    {
        if ($monthlyBudget <= 0) {
            return 'belum_diatur';
        }

        if ($budgetUsedPercentage >= 100) {
            return 'boros';
        }

        if ($budgetUsedPercentage >= 70) {
            return 'waspada';
        }

        return 'aman';
    }

    private function topSpendingCategory(array $expenseByCategory): ?array
    {
        if ($expenseByCategory === []) {
            return null;
        }

        $category = $expenseByCategory[0];

        return [
            'name' => $category['category_name'],
            'icon' => $category['category_icon'],
            'amount' => $category['amount'],
            'percentage' => $category['percentage_of_expense'],
        ];
    }

    private function buildInsight(User $user): array
    {
        if (! $this->hasCategoryTable()) {
            return [
                'text' => 'Catatan bulan ini siap dipantau',
                'subtext' => 'Gunakan chat untuk mencatat transaksi baru',
                'icon' => '💡',
            ];
        }

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        // --- Total expense this month vs last month ---
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonthExpense = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $lastMonthExpense = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        // --- Budget health insight ---
        $monthlyBudget = (int) $user->monthly_budget;
        if ($monthlyBudget > 0 && $thisMonthExpense > 0) {
            $usedPct = round(($thisMonthExpense / $monthlyBudget) * 100);
            if ($usedPct >= 100) {
                return [
                    'text' => 'Budget bulan ini sudah habis!',
                    'subtext' => 'Total pengeluaran Rp '.number_format($thisMonthExpense, 0, ',', '.').' dari Rp '.number_format($monthlyBudget, 0, ',', '.'),
                    'icon' => '⚠️',
                ];
            }
            if ($usedPct >= 70) {
                $remaining = $monthlyBudget - $thisMonthExpense;
                return [
                    'text' => "Budget tersisa {$usedPct}% — waspada!",
                    'subtext' => 'Sisa Rp '.number_format($remaining, 0, ',', '.').' untuk '. now()->daysInMonth - now()->day .' hari ke depan',
                    'icon' => '⚠️',
                ];
            }
        }

        // --- Month-over-month expense trend ---
        if ($lastMonthExpense > 0 && $thisMonthExpense > 0) {
            $diff = $thisMonthExpense - $lastMonthExpense;
            $pct = (int) round(abs($diff) / $lastMonthExpense * 100);

            if ($diff > 0) {
                return [
                    'text' => "Pengeluaran naik {$pct}% dari bulan lalu",
                    'subtext' => 'Bulan lalu Rp '.number_format($lastMonthExpense, 0, ',', '.').' → bulan ini Rp '.number_format($thisMonthExpense, 0, ',', '.'),
                    'icon' => '📈',
                ];
            }

            if ($diff < 0) {
                return [
                    'text' => "Pengeluaran turun {$pct}% dari bulan lalu",
                    'subtext' => 'Bulan lalu Rp '.number_format($lastMonthExpense, 0, ',', '.').' → bulan ini Rp '.number_format($thisMonthExpense, 0, ',', '.'),
                    'icon' => '📉',
                ];
            }
        }

        // --- Top category insight ---
        $expenseByCategory = $this->expenseByCategory($user, $start, $end, $thisMonthExpense);
        $topCategory = $this->topSpendingCategory($expenseByCategory);
        if ($topCategory !== null && $topCategory['percentage'] >= 40) {
            return [
                'text' => "{$topCategory['name']} mendominasi {$topCategory['percentage']}% pengeluaran",
                'subtext' => 'Total Rp '.number_format($topCategory['amount'], 0, ',', '.'),
                'icon' => '💡',
            ];
        }

        // --- Week-over-week trend (top category or total) ---
        $thisWeekExpense = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');

        $lastWeekExpense = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('amount');

        if ($lastWeekExpense > 0 && $thisWeekExpense > $lastWeekExpense) {
            $pct = (int) round((($thisWeekExpense - $lastWeekExpense) / $lastWeekExpense) * 100);
            return [
                'text' => "Pengeluaran minggu ini +{$pct}%",
                'subtext' => 'Dibanding minggu lalu · Rp '.number_format($thisWeekExpense - $lastWeekExpense, 0, ',', '.'),
                'icon' => '💡',
            ];
        }

        return [
            'text' => 'Catatan bulan ini siap dipantau',
            'subtext' => 'Gunakan chat untuk mencatat transaksi baru',
            'icon' => '💡',
        ];
    }

    private function hasCategoryTable(): bool
    {
        return Schema::hasTable('categories');
    }
}

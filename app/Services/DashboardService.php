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

        return [
            'current_month_balance' => $totalIncome - $totalExpense,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'insight_strip' => $this->buildInsight($user),
            'recent_transactions' => $this->recentTransactions($user),
            'expense_by_category' => $this->expenseByCategory($user, $start, $end, $totalExpense),
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

    private function buildInsight(User $user): array
    {
        if (! $this->hasCategoryTable()) {
            return [
                'text' => 'Catatan bulan ini siap dipantau',
                'subtext' => 'Gunakan chat untuk mencatat transaksi baru',
                'icon' => '💡',
            ];
        }

        $food = fn (Carbon $from, Carbon $to): int => (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$from, $to])
            ->whereHas('category', fn (Builder $query) => $query->where('name', 'MAKANAN'))
            ->sum('amount');

        $thisWeek = $food(now()->startOfWeek(), now()->endOfWeek());
        $lastWeek = $food(now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek());

        if ($lastWeek > 0 && $thisWeek > $lastWeek) {
            $percentage = (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100);

            return [
                'text' => "Pengeluaran makanan +{$percentage}%",
                'subtext' => 'Dibanding minggu lalu · Rp '.number_format($thisWeek - $lastWeek, 0, ',', '.'),
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

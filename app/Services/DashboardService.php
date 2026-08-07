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
            'monthly_recap' => $this->getMonthlyRecap($user),
        ];
    }

    public function getMonthlyRecap(User $user, int $month = null, int $year = null): array
    {
        $date = Carbon::createFromDate($year, $month, 1);
        $start = $date->startOfMonth();
        $end = $date->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        $transactions = $this->completedTransactions($user)
            ->whereBetween('transaction_date', [$start, $end])
            ->get();

        $totalIncome = (int) $transactions->where('type', Transaction::TYPE_INCOME)->sum('amount');
        $totalExpense = (int) $transactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;

        // Previous month data for comparison
        $prevStart = (clone $start)->subMonth();
        $prevEnd = (clone $end)->subMonth();
        $prevMonthLabel = $prevStart->format('M Y');
        $prevTransactions = $this->completedTransactions($user)
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->get();
        $prevTotalIncome = (int) $prevTransactions->where('type', Transaction::TYPE_INCOME)->sum('amount');
        $prevTotalExpense = (int) $prevTransactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount');
        $prevNetCashflow = $prevTotalIncome - $prevTotalExpense;

        // Summary deltas
        $incomeDelta = $prevTotalIncome > 0 ? (int) round((($totalIncome - $prevTotalIncome) / $prevTotalIncome) * 100) : 0;
        $expenseDelta = $prevTotalExpense > 0 ? (int) round((($totalExpense - $prevTotalExpense) / $prevTotalExpense) * 100) : 0;
        $savingsDelta = $prevNetCashflow !== 0 ? (int) round((($netCashflow - $prevNetCashflow) / abs($prevNetCashflow)) * 100) : 0;

        // Weekly expense breakdown (4 weeks)
        $weekExpenses = [];
        for ($w = 1; $w <= 4; $w++) {
            $weekStart = (clone $start)->addWeeks($w - 1);
            $weekEnd = $weekStart->copy()->endOfWeek();
            if ($weekStart->month !== $date->month) {
                $weekStart = $start->copy();
            }
            if ($weekEnd->month !== $date->month) {
                $weekEnd = $end->copy();
            }
            $weekAmount = (int) $this->completedTransactions($user)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->whereBetween('transaction_date', [$weekStart, $weekEnd])
                ->sum('amount');
            $weekExpenses[] = ['week' => $w, 'label' => "M{$w}", 'amount' => $weekAmount];
        }
        $maxWeek = collect($weekExpenses)->max('amount');

        // Top 5 expense transactions
        $topExpenses = $transactions->where('type', Transaction::TYPE_EXPENSE)
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->map(fn(Transaction $t) => [
                'id' => $t->id,
                'description' => $t->description ?: 'Tanpa deskripsi',
                'amount' => (int) $t->amount,
                'category_name' => $t->category?->name ?? 'LAINNYA',
                'category_icon' => $t->category?->icon ?? '📌',
                'date' => $t->transaction_date?->format('d M') ?? '',
            ])
            ->all();

        // Category breakdown
        $expenseByCategory = $this->getCategoryBreakdown($user, $start, $end, Transaction::TYPE_EXPENSE);
        $incomeByCategory = $this->getCategoryBreakdown($user, $start, $end, Transaction::TYPE_INCOME);

        // Category comparison vs previous month
        $comparison = [];
        if ($this->hasCategoryTable() && $totalExpense > 0) {
            $prevCategoryBreakdown = $this->getCategoryBreakdown($user, $prevStart, $prevEnd, Transaction::TYPE_EXPENSE);
            $prevCatMap = collect($prevCategoryBreakdown)->keyBy('category_name');

            foreach ($expenseByCategory as $cat) {
                $prevCat = $prevCatMap->get($cat['category_name']);
                $prevAmount = $prevCat ? $prevCat['amount'] : 0;
                $delta = $prevAmount > 0 ? (int) round((($cat['amount'] - $prevAmount) / $prevAmount) * 100) : ($cat['amount'] > 0 ? 100 : 0);

                $comparison[] = [
                    'category_name' => $cat['category_name'],
                    'category_icon' => $cat['category_icon'],
                    'current_amount' => $cat['amount'],
                    'prev_amount' => $prevAmount,
                    'delta' => $delta,
                ];
            }
        }

        // AI Insights (algorithmic, not LLM)
        $aiInsights = $this->generateRecapInsights($user, $start, $end, $totalIncome, $totalExpense, $expenseByCategory, $weekExpenses);
        $financialScore = $this->calculateFinancialScore($savingsRate = $totalIncome > 0 ? (int) round(($netCashflow / $totalIncome) * 100) : 0, $expenseDelta, $totalIncome, $totalExpense);

        return [
            'month_year' => $date->format('F Y'),
            'month_label' => $date->format('M'),
            'prev_month_label' => $prevMonthLabel,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cashflow' => $netCashflow,
            'savings_rate' => $savingsRate,
            'days_in_month' => $daysInMonth,
            'summary_delta' => [
                'income' => $incomeDelta,
                'expense' => $expenseDelta,
                'savings' => $savingsDelta,
            ],
            'week_expenses' => $weekExpenses,
            'week_max' => $maxWeek,
            'top_expenses' => $topExpenses,
            'expense_by_category' => $expenseByCategory,
            'income_by_category' => $incomeByCategory,
            'comparison' => $comparison,
            'ai_insights' => $aiInsights,
            'financial_score' => $financialScore,
        ];
    }

    private function generateRecapInsights(User $user, Carbon $start, Carbon $end, int $totalIncome, int $totalExpense, array $expenseByCategory, array $weekExpenses): array
    {
        $insights = [];
        $prevStart = (clone $start)->subMonth();
        $prevEnd = (clone $end)->subMonth();

        // 1. Top category insight
        if (!empty($expenseByCategory)) {
            $top = $expenseByCategory[0];
            if ($top['percentage'] >= 25) {
                $prevCatAmount = (int) $this->completedTransactions($user)
                    ->where('type', Transaction::TYPE_EXPENSE)
                    ->whereBetween('transaction_date', [$prevStart, $prevEnd])
                    ->whereHas('category', fn($q) => $q->where('name', $top['category_name']))
                    ->sum('amount');

                $catDelta = $prevCatAmount > 0 ? (int) round((($top['amount'] - $prevCatAmount) / $prevCatAmount) * 100) : 0;

                if ($catDelta > 10) {
                    $insights[] = [
                        'icon' => '⚠️',
                        'title' => "{$top['category_name']} melebihi batas wajar",
                        'description' => "Pengeluaran {$top['category_name']} bulan ini Rp " . number_format($top['amount'], 0, ',', '.') . " — naik {$catDelta}% dari bulan lalu.",
                        'type' => 'warn',
                    ];
                } elseif ($catDelta < -10) {
                    $insights[] = [
                        'icon' => '✅',
                        'title' => "{$top['category_name']} berhasil ditekan",
                        'description' => "Turun " . abs($catDelta) . "% dari bulan lalu. Pertahankan!",
                        'type' => 'good',
                    ];
                }
            }
        }

        // 2. Savings rate insight
        $savingsRate = $totalIncome > 0 ? (int) round((($totalIncome - $totalExpense) / $totalIncome) * 100) : 0;
        if ($savingsRate >= 30) {
            $insights[] = [
                'icon' => '✅',
                'title' => "Saving rate {$savingsRate}% — di atas rata-rata",
                'description' => "Rata-rata saving rate Indonesia sekitar 18–22%. Kamu jauh di atasnya.",
                'type' => 'good',
            ];
        } elseif ($savingsRate < 0 && $totalIncome > 0) {
            $insights[] = [
                'icon' => '⚠️',
                'title' => "Defisit bulan ini!",
                'description' => "Pengeluaran melebihi pemasukan sebesar Rp " . number_format(abs($totalIncome - $totalExpense), 0, ',', '.') . ".",
                'type' => 'warn',
            ];
        }

        // 3. Week pattern
        $maxWeekIdx = 0;
        $maxWeekVal = 0;
        foreach ($weekExpenses as $i => $w) {
            if ($w['amount'] > $maxWeekVal) {
                $maxWeekVal = $w['amount'];
                $maxWeekIdx = $i;
            }
        }
        if ($maxWeekIdx >= 2 && $maxWeekVal > 0) {
            $insights[] = [
                'icon' => '📅',
                'title' => "Pola pengeluaran minggu ke-" . ($maxWeekIdx + 1) . " tinggi",
                'description' => "Minggu ke-" . ($maxWeekIdx + 1) . " jadi puncak pengeluaran. Siapkan budget lebih ketat di periode itu.",
                'type' => 'warn',
            ];
        }

        // 4. General
        if (empty($insights)) {
            $insights[] = [
                'icon' => '💡',
                'title' => 'Pengeluaran bulan ini stabil',
                'description' => 'Tidak ada pola mencolok. Tetap pantau transaksi harian.',
                'type' => 'info',
            ];
        }

        return $insights;
    }

    private function calculateFinancialScore(int $savingsRate, int $expenseDelta, int $totalIncome, int $totalExpense): int
    {
        $score = 50; // baseline

        // Savings rate contribution (up to +30)
        if ($savingsRate >= 50) $score += 30;
        elseif ($savingsRate >= 30) $score += 20;
        elseif ($savingsRate >= 10) $score += 10;
        elseif ($savingsRate < 0) $score -= 20;

        // Expense control (up to +20)
        if ($expenseDelta < -10) $score += 20;
        elseif ($expenseDelta < 0) $score += 10;
        elseif ($expenseDelta > 20) $score -= 15;
        elseif ($expenseDelta > 10) $score -= 5;

        return max(0, min(100, $score));
    }

    private function completedTransactions(User $user): Builder
    {
        return Transaction::query()
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $user->id))
            ->where(function (Builder $query) {
                $query->where('status', Transaction::STATUS_COMPLETED)
                    ->orWhereNull('status');
            });
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

    private function getCategoryBreakdown(User $user, Carbon $start, Carbon $end, string $type): array
    {
        if (! $this->hasCategoryTable()) {
            return [];
        }

        $transactions = $this->completedTransactions($user)
            ->where('type', $type)
            ->whereBetween('transaction_date', [$start, $end])
            ->with('category')
            ->get();

        $totalAmount = (int) $transactions->sum('amount');
        if ($totalAmount <= 0) {
            return [];
        }

        return $transactions
            ->groupBy(fn (Transaction $transaction) => $transaction->category?->name ?? 'LAINNYA')
            ->map(function ($transactions) use ($totalAmount) {
                $first = $transactions->first();
                $amount = (int) $transactions->sum('amount');

                return [
                    'category_name' => $first->category?->name ?? 'LAINNYA',
                    'category_icon' => $first->category?->icon ?? '📌',
                    'amount' => $amount,
                    'percentage' => (int) round(($amount / $totalAmount) * 100),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }
}

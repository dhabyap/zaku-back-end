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
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
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

        // Weekly expense breakdown (dynamic — 4 to 6 weeks depending on month)
        $numWeeks = (int) ceil($daysInMonth / 7);
        $weekExpenses = [];
        for ($w = 1; $w <= $numWeeks; $w++) {
            $weekStart = $start->copy()->addDays(($w - 1) * 7);
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->greaterThan($end)) {
                $weekEnd = $end->copy();
            }
            $weekAmount = (int) $this->completedTransactions($user)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->whereBetween('transaction_date', [$weekStart, $weekEnd])
                ->sum('amount');
            $weekExpenses[] = [
                'week' => $w,
                'label' => 'M' . $w,
                'amount' => $weekAmount,
                'start' => $weekStart->format('d M'),
                'end' => $weekEnd->format('d M'),
            ];
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
        $savingsRate = $totalIncome > 0 ? (int) round((($totalIncome - $totalExpense) / $totalIncome) * 100) : 0;
        $netCashflow = $totalIncome - $totalExpense;

        // 1. Defisit / surplus — always show
        if ($totalIncome > 0 && $netCashflow < 0) {
            $deficitPct = (int) round((abs($netCashflow) / $totalIncome) * 100);
            $insights[] = [
                'icon' => '🚨',
                'title' => 'Defisit bulan ini!',
                'description' => 'Pengeluaran melebihi pemasukan sebesar Rp ' . number_format(abs($netCashflow), 0, ',', '.') . " ({$deficitPct}% dari pemasukan).",
                'type' => 'warn',
            ];
        } elseif ($savingsRate >= 30) {
            $insights[] = [
                'icon' => '🎉',
                'title' => "Saving rate {$savingsRate}% — excellent!",
                'description' => "Kamu simpan Rp " . number_format($netCashflow, 0, ',', '.') . " bulan ini. Rata-rata nasional cuma 18–22%.",
                'type' => 'good',
            ];
        } elseif ($savingsRate >= 10) {
            $insights[] = [
                'icon' => '💪',
                'title' => "Saving rate {$savingsRate}% — lumayan!",
                'description' => 'Masih ada ruang naik. Target 30% untuk keamanan finansial lebih baik.',
                'type' => 'info',
            ];
        }

        // 2. Daily spending velocity
        $daysElapsed = (int) $start->diffInDays(Carbon::now()->min($end));
        if ($daysElapsed > 0 && $totalIncome > 0) {
            $dailyAvg = (int) round($totalExpense / $daysElapsed);
            $monthlyProjection = $dailyAvg * $start->daysInMonth;
            if ($monthlyProjection > $totalIncome * 1.1 && $daysElapsed >= 5) {
                $insights[] = [
                    'icon' => '📉',
                    'title' => 'Proyeksi: pengeluaran akan tembus batas',
                    'description' => "Rata-rata harian Rp " . number_format($dailyAvg, 0, ',', '.') . ". Kalau terus, bulan ini habis Rp " . number_format($monthlyProjection, 0, ',', '.') . ".",
                    'type' => 'warn',
                ];
            } elseif ($monthlyProjection < $totalIncome * 0.7 && $daysElapsed >= 5) {
                $insights[] = [
                    'icon' => '✅',
                    'title' => 'Proyeksi: aman sampai akhir bulan',
                    'description' => "Rata-rata harian Rp " . number_format($dailyAvg, 0, ',', '.') . ". Proyeksi akhir bulan cuma " . (int) round(($monthlyProjection / max($totalIncome, 1)) * 100) . "% dari pemasukan.",
                    'type' => 'good',
                ];
            }
        }

        // 3. Top category dominance
        if (!empty($expenseByCategory)) {
            $top = $expenseByCategory[0];
            if ($top['percentage'] >= 40) {
                $insights[] = [
                    'icon' => '📊',
                    'title' => "{$top['category_name']} dominasi pengeluaran",
                    'description' => "{$top['category_name']} makan " . $top['percentage'] . "% total pengeluaran (Rp " . number_format($top['amount'], 0, ',', '.') . "). Coba diversifikasi.",
                    'type' => 'warn',
                ];
            } elseif ($top['percentage'] >= 25) {
                // Check vs previous month
                $prevCatAmount = (int) $this->completedTransactions($user)
                    ->where('type', Transaction::TYPE_EXPENSE)
                    ->whereBetween('transaction_date', [$prevStart, $prevEnd])
                    ->whereHas('category', fn($q) => $q->where('name', $top['category_name']))
                    ->sum('amount');

                if ($prevCatAmount > 0) {
                    $catDelta = (int) round((($top['amount'] - $prevCatAmount) / $prevCatAmount) * 100);
                    if ($catDelta > 15) {
                        $insights[] = [
                            'icon' => '⚠️',
                            'title' => "{$top['category_name']} naik {$catDelta}%",
                            'description' => 'Bulan ini Rp ' . number_format($top['amount'], 0, ',', '.') . ' vs Rp ' . number_format($prevCatAmount, 0, ',', '.') . ' bulan lalu.',
                            'type' => 'warn',
                        ];
                    } elseif ($catDelta < -15) {
                        $insights[] = [
                            'icon' => '✅',
                            'title' => "{$top['category_name']} turun " . abs($catDelta) . '%',
                            'description' => 'Dari Rp ' . number_format($prevCatAmount, 0, ',', '.') . ' ke Rp ' . number_format($top['amount'], 0, ',', '.') . '. Pertahankan!',
                            'type' => 'good',
                        ];
                    }
                }
            }
        }

        // 4. Week-over-week spike detection
        if (count($weekExpenses) >= 2) {
            $amounts = array_column($weekExpenses, 'amount');
            $avgWeek = (int) round(array_sum($amounts) / count($amounts));
            if ($avgWeek > 0) {
                foreach ($weekExpenses as $w) {
                    if ($w['amount'] > $avgWeek * 1.5 && $w['amount'] > 0) {
                        $spikePct = (int) round(($w['amount'] / max($avgWeek, 1) - 1) * 100);
                        $insights[] = [
                            'icon' => '📅',
                            'title' => "{$w['label']} pengeluaran spike +{$spikePct}%",
                            'description' => "Minggu {$w['label']} (" . $w['start'] . '–' . $w['end'] . ") habis Rp " . number_format($w['amount'], 0, ',', '.') . ' — jauh di atas rata-rata.',
                            'type' => 'warn',
                        ];
                        break;
                    }
                }
            }
        }

        // 5. Previous month comparison
        $prevTotalExpense = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');
        if ($prevTotalExpense > 0 && $totalExpense > 0) {
            $expDelta = (int) round((($totalExpense - $prevTotalExpense) / $prevTotalExpense) * 100);
            if ($expDelta > 20) {
                $insights[] = [
                    'icon' => '📈',
                    'title' => "Pengeluaran naik {$expDelta}% vs bulan lalu",
                    'description' => 'Rp ' . number_format($prevTotalExpense, 0, ',', '.') . ' → Rp ' . number_format($totalExpense, 0, ',', '.') . '. Cek pengeluaran mana yang naik.',
                    'type' => 'warn',
                ];
            } elseif ($expDelta < -15) {
                $insights[] = [
                    'icon' => '📉',
                    'title' => "Pengeluaran turun " . abs($expDelta) . '% vs bulan lalu',
                    'description' => 'Dari Rp ' . number_format($prevTotalExpense, 0, ',', '.') . ' ke Rp ' . number_format($totalExpense, 0, ',', '.') . '. Bagus!',
                    'type' => 'good',
                ];
            }
        }

        // 6. Income insight
        $prevTotalIncome = (int) $this->completedTransactions($user)
            ->where('type', Transaction::TYPE_INCOME)
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');
        if ($prevTotalIncome > 0 && $totalIncome > 0) {
            $incDelta = (int) round((($totalIncome - $prevTotalIncome) / $prevTotalIncome) * 100);
            if ($incDelta < -20) {
                $insights[] = [
                    'icon' => '💰',
                    'title' => "Pemasukan turun {$incDelta}%",
                    'description' => 'Dari Rp ' . number_format($prevTotalIncome, 0, ',', '.') . ' ke Rp ' . number_format($totalIncome, 0, ',', '.') . '. Cek sumber penghasilan.',
                    'type' => 'warn',
                ];
            } elseif ($incDelta > 20) {
                $insights[] = [
                    'icon' => '💰',
                    'title' => "Pemasukan naik +{$incDelta}%",
                    'description' => 'Dari Rp ' . number_format($prevTotalIncome, 0, ',', '.') . ' ke Rp ' . number_format($totalIncome, 0, ',', '.') . '. Great job!',
                    'type' => 'good',
                ];
            }
        }

        // 7. Expense concentration — many small categories = good diversification
        if (count($expenseByCategory) >= 4 && !empty($expenseByCategory)) {
            $topPct = $expenseByCategory[0]['percentage'] ?? 0;
            if ($topPct < 30) {
                $insights[] = [
                    'icon' => '✅',
                    'title' => 'Pengeluaran tersebar merata',
                    'description' => 'Tidak ada kategori yang terlalu mendominasi. Pola belanja sehat.',
                    'type' => 'good',
                ];
            }
        }

        // 8. Fallback — always at least 1 insight
        if (empty($insights)) {
            $insights[] = [
                'icon' => '💡',
                'title' => 'Pengeluaran bulan ini stabil',
                'description' => 'Tidak ada pola mencolok. Pertahankan pola belanja saat ini.',
                'type' => 'info',
            ];
        }

        return array_slice($insights, 0, 5); // max 5 insights
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
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $user->id));
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
                    'percentage' => (int) round(($amount / $totalExpense) * 100),
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
            'percentage' => $category['percentage'],
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

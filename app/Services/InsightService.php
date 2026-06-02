<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class InsightService
{
    public function getInsights(User $user, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $wallet = $user->wallet;
        if (! $wallet) {
            return [];
        }

        $monthlyBudget = (float) $user->monthly_budget;

        $expensesQuery = Transaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalExpenses = (float) $expensesQuery->sum('amount');

        $insights = [];

        // Budget rules
        if ($monthlyBudget > 0) {
            $pct = ($totalExpenses / $monthlyBudget) * 100;

            if ($pct >= 100) {
                $insights[] = [
                    'type' => 'budget_risk',
                    'title' => 'Budget sudah terlampaui',
                    'message' => 'Pengeluaran bulan ini sudah mencapai atau melewati 100% budget.',
                    'severity' => 'danger',
                    'related_category' => null,
                    'related_transaction_id' => null,
                ];
            } elseif ($pct >= 80) {
                $insights[] = [
                    'type' => 'budget_risk',
                    'title' => 'Budget mulai menipis',
                    'message' => 'Pengeluaran bulan ini sudah melewati 80% budget.',
                    'severity' => 'warning',
                    'related_category' => null,
                    'related_transaction_id' => null,
                ];
            }
        }

        // Largest expense transaction
        $largestTx = (clone $expensesQuery)->orderByDesc('amount')->first();
        if ($largestTx) {
            $insights[] = [
                'type' => 'largest_expense',
                'title' => 'Transaksi terbesar bulan ini',
                'message' => 'Ada transaksi expense besar bulan ini.',
                'severity' => 'info',
                'related_category' => $largestTx->category?->name ?? null,
                'related_transaction_id' => $largestTx->id,
            ];
        }

        // Largest expense category
        $largestCategory = (clone $expensesQuery)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        if ($largestCategory && $largestCategory->category_id) {
            $insights[] = [
                'type' => 'largest_category',
                'title' => 'Kategori pengeluaran terbesar',
                'message' => 'Kategori ini menyumbang pengeluaran terbesar bulan ini.',
                'severity' => 'info',
                'related_category' => optional($largestCategory->category)->name ?? null,
                'related_transaction_id' => null,
            ];
        }

        return $insights;
    }
}

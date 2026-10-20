<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class BudgetNotificationService
{
    public function checkForUser(User $user): int
    {
        $created = 0;

        Budget::where('user_id', $user->id)->with('category')->each(function (Budget $budget) use ($user, &$created) {
            $start = $budget->start_date
                ? Carbon::parse($budget->start_date)->startOfDay()
                : now()->startOfMonth();
            $end = $budget->end_date
                ? Carbon::parse($budget->end_date)->endOfDay()
                : now()->endOfMonth();

            if (now()->lt($start) || now()->gt($end) || $budget->amount <= 0) {
                return;
            }

            $spent = Transaction::whereHas('wallet', fn ($query) => $query->where('user_id', $user->id))
                ->where('category_id', $budget->category_id)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');

            $percentage = (int) floor(($spent / $budget->amount) * 100);
            $thresholds = [];

            if ($percentage >= 80) {
                $thresholds[] = ['budget_warning', 'Budget hampir habis', 80];
            }
            if ($percentage >= 100) {
                $thresholds[] = ['budget_risk', 'Budget tercapai', 100];
            }

            foreach ($thresholds as [$type, $title, $threshold]) {
                $alreadySent = Notification::where('user_id', $user->id)
                    ->where('type', $type)
                    ->where('data->budget_id', $budget->id)
                    ->where('data->period_start', $start->toDateString())
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => sprintf(
                        'Pengeluaran kategori %s sudah mencapai %d%% budget (%s dari %s).',
                        $budget->category?->name ?? 'lainnya',
                        $percentage,
                        'Rp ' . number_format($spent, 0, ',', '.'),
                        'Rp ' . number_format($budget->amount, 0, ',', '.')
                    ),
                    'data' => [
                        'budget_id' => $budget->id,
                        'category_name' => $budget->category?->name,
                        'spent' => $spent,
                        'budget_amount' => $budget->amount,
                        'percentage' => $percentage,
                        'threshold' => $threshold,
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                    ],
                    'is_read' => false,
                ]);
                $created++;
            }
        });

        return $created;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Budget;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckBudgetNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-budget-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check budget thresholds and create notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $budgets = Budget::where('user_id', $user->id)->get();

            foreach ($budgets as $budget) {
                // Get completed transactions within budget period
                $start = $budget->start_date ? Carbon::parse($budget->start_date)->startOfDay() : Carbon::now()->startOfMonth();
                $end = $budget->end_date ? Carbon::parse($budget->end_date)->endOfDay() : Carbon::now()->endOfMonth();

                $spent = \App\Models\Transaction::whereHas('wallet', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->whereBetween('transaction_date', [$start, $end])
                    ->where('status', \App\Models\Transaction::STATUS_COMPLETED)
                    ->sum('amount');

                $budgetAmount = $budget->amount;
                $pct = $budgetAmount > 0 ? round(($spent / $budgetAmount) * 100) : 0;

                // Determine threshold type
                $type = null;
                $title = '';
                $message = '';

                if ($pct >= 100) {
                    $type = 'budget_risk';
                    $title = 'Budget tercapai';
                    $message = "Pengeluaran Anda untuk kategori {$budget->category->name} sudah mencapai 100% budget (Rp " . number_format($spent,0,',','.') . " dari Rp " . number_format($budgetAmount,0,',','.') . ").";
                } elseif ($pct >= 80) {
                    $type = 'budget_warning';
                    $title = 'Budget warning';
                    $message = "Pengeluaran Anda untuk kategori {$budget->category->name} sudah melebihi 80% budget (Rp " . number_format($spent,0,',','.') . " dari Rp " . number_format($budgetAmount,0,',','.') . ").";
                }

                if ($type) {
                    // Avoid duplicate notifications: check if there is an unread notification of same type for this budget in the last 24 hours
                    $recentNotification = Notification::where('user_id', $user->id)
                        ->where('type', $type)
                        ->where('data->budget_id', $budget->id)
                        ->where('is_read', false)
                        ->where('created_at', '>=', Carbon::now()->subHours(24))
                        ->first();

                    if (!$recentNotification) {
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => $type,
                            'title' => $title,
                            'message' => $message,
                            'data' => [
                                'budget_id' => $budget->id,
                                'category_name' => $budget->category->name,
                                'spent' => $spent,
                                'budget_amount' => $budgetAmount,
                                'percentage' => $pct,
                            ],
                        ]);

                        Log::info("Created budget notification for user {$user->id}, budget {$budget->id}, type {$type}");
                    }
                }
            }
        }

        $this->info('Budget notification check completed.');
    }
}
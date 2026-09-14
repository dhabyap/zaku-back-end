<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    use ApiResponse;

    public function publicStats(): JsonResponse
    {
        try {
            $stats = cache()->remember('stats:public', 300, function () {
                $userCount = User::count();
                $txCount = Transaction::count();
                $totalAmount = (int) Transaction::sum('amount');
                $totalIncome = (int) Transaction::where('type', 'income')->sum('amount');
                $totalExpense = (int) Transaction::where('type', 'expense')->sum('amount');

                return [
                    'user_count' => $userCount,
                    'active_users' => $userCount,
                    'transaction_count' => $txCount,
                    'total_amount' => $totalAmount,
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                ];
            });
        } catch (\Throwable $e) {
            $stats = [
                'user_count' => 0,
                'active_users' => 0,
                'transaction_count' => 0,
                'total_amount' => 0,
                'total_income' => 0,
                'total_expense' => 0,
            ];
        }

        return $this->successResponse($stats, 'Statistik publik berhasil diambil');
    }
}
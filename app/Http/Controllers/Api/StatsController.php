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
            $userCount = User::count();
            $txCount = Transaction::count();
            $totalAmount = (int) Transaction::sum('amount');
            $totalIncome = (int) Transaction::where('type', 'income')->sum('amount');
            $totalExpense = (int) Transaction::where('type', 'expense')->sum('amount');

            // Active users replaced with total registered users
            $activeUsers = $userCount;
        } catch (\Throwable $e) {
            $userCount = 0;
            $txCount = 0;
            $totalAmount = 0;
            $totalIncome = 0;
            $totalExpense = 0;
            $activeUsers = 0;
        }

        return $this->successResponse([
            'user_count' => $userCount,
            'active_users' => $activeUsers,
            'transaction_count' => $txCount,
            'total_amount' => $totalAmount,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
        ], 'Statistik publik berhasil diambil');
    }
}
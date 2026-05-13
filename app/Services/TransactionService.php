<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create(
        User $user,
        Category $category,
        string $type,
        int $amount,
        string $description,
        string $source = Transaction::SOURCE_MANUAL,
        ?string $rawMessage = null,
    ): Transaction {
        return DB::transaction(function () use ($user, $category, $type, $amount, $description, $source, $rawMessage) {
            $wallet = $user->wallet ?: Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]);

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'category_id' => $category->id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'source' => $source,
                'raw_message' => $rawMessage,
                'status' => Transaction::STATUS_COMPLETED,
                'transaction_date' => now(),
            ]);

            if ($type === Transaction::TYPE_INCOME) {
                $wallet->addBalance($amount);
            } else {
                $wallet->balance = number_format(((float) $wallet->balance) - $amount, 2, '.', '');
                $wallet->save();
            }

            return $transaction->load('category');
        });
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
        Carbon|string|null $transactionDate = null,
    ): Transaction {
        return DB::transaction(function () use ($user, $category, $type, $amount, $description, $source, $rawMessage, $transactionDate) {
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
                'transaction_date' => $transactionDate ? Carbon::parse($transactionDate) : now(),
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

    public function update(
        Transaction $transaction,
        ?Category $category,
        ?string $type,
        ?int $amount,
        ?string $description,
        ?string $transactionDate,
    ): Transaction {
        return DB::transaction(function () use ($transaction, $category, $type, $amount, $description, $transactionDate) {
            $wallet = $transaction->wallet;

            $oldAmount = (int) $transaction->amount;
            $oldType = $transaction->type;

            $newType = $type ?? $oldType;
            $newAmount = $amount ?? $oldAmount;

            // If amount or type changed, revert old transaction effect first
            if ($newAmount !== $oldAmount || $newType !== $oldType) {
                if ($oldType === Transaction::TYPE_INCOME) {
                    $wallet->balance = number_format(((float) $wallet->balance) - $oldAmount, 2, '.', '');
                } else {
                    $wallet->balance = number_format(((float) $wallet->balance) + $oldAmount, 2, '.', '');
                }
                $wallet->save();
            }

            $transaction->category_id = $category?->id ?? $transaction->category_id;
            $transaction->type = $newType;
            $transaction->amount = $newAmount;

            if ($description !== null) {
                $transaction->description = $description;
            }

            if ($transactionDate !== null) {
                $transaction->transaction_date = $transactionDate;
            }

            $transaction->save();

            // Apply new transaction effect if changed
            if ($newAmount !== $oldAmount || $newType !== $oldType) {
                if ($newType === Transaction::TYPE_INCOME) {
                    $wallet->balance = number_format(((float) $wallet->balance) + $newAmount, 2, '.', '');
                } else {
                    $wallet->balance = number_format(((float) $wallet->balance) - $newAmount, 2, '.', '');
                }

                $wallet->save();
            }

            return $transaction->load('category');
        });
    }
}

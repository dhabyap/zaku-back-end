<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'zaku:process-recurring';

    protected $description = 'Process all due recurring transactions';

    public function handle(): int
    {
        $processed = 0;
        $errors = 0;

        RecurringTransaction::due()->chunk(100, function ($recurrings) use (&$processed, &$errors) {
            foreach ($recurrings as $recurring) {
                DB::beginTransaction();
                try {
                    $wallet = $recurring->wallet;

                    // Create transaction
                    Transaction::create([
                        'wallet_id' => $wallet->id,
                        'category_id' => $recurring->category_id,
                        'type' => $recurring->type,
                        'amount' => $recurring->amount_cents,
                        'description' => $recurring->description,
                        'source' => Transaction::SOURCE_RECURRING,
                        'status' => Transaction::STATUS_COMPLETED,
                        'transaction_date' => now(),
                    ]);

                    // Update wallet balance
                    if ($recurring->type === Transaction::TYPE_INCOME) {
                        $wallet->addBalance($recurring->amount_cents);
                    } else {
                        $wallet->deductBalance($recurring->amount_cents);
                    }

                    // Calculate next date
                    $nextDate = $recurring->calculateNextDate();

                    // Check if completed
                    if ($recurring->end_date && $nextDate->gt($recurring->end_date)) {
                        $recurring->update([
                            'status' => RecurringTransaction::STATUS_COMPLETED,
                            'last_executed_at' => now()->toDateString(),
                            'next_execution_date' => $nextDate,
                        ]);
                    } else {
                        $recurring->update([
                            'last_executed_at' => now()->toDateString(),
                            'next_execution_date' => $nextDate,
                        ]);
                    }

                    DB::commit();
                    $processed++;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors++;
                    $this->error("Failed recurring #{$recurring->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Processed: {$processed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

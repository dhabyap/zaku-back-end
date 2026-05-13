<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMoneyRequest;
use App\Http\Requests\WalletAmountRequest;
use App\Http\Requests\WithdrawRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletController extends Controller
{
    use ApiResponse;

    public function balance(Request $request): JsonResponse
    {
        $wallet = $this->walletFor($request->user());

        return $this->successResponse([
            'balance' => (int) $wallet->balance,
            'total_income' => (int) $this->transactions($request->user()->id)
                ->where('type', Transaction::TYPE_INCOME)
                ->sum('amount'),
            'total_expense' => (int) $this->transactions($request->user()->id)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->sum('amount'),
        ], 'Saldo wallet berhasil diambil');
    }

    public function topup(WalletAmountRequest $request): JsonResponse
    {
        $wallet = DB::transaction(function () use ($request) {
            $wallet = $this->walletFor($request->user());
            $amount = $request->integer('amount');

            $wallet->addBalance($amount);
            $this->recordTransaction($wallet, Transaction::TYPE_INCOME, $amount, 'Top up wallet');

            return $wallet->refresh();
        });

        return $this->successResponse([
            'balance' => (int) $wallet->balance,
            'message' => 'Top up berhasil.',
        ], 'Top up berhasil.');
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $wallet = DB::transaction(function () use ($request) {
                $wallet = $this->walletFor($request->user());
                $amount = $request->integer('amount');

                $wallet->deductBalance($amount);
                $this->recordTransaction(
                    $wallet,
                    Transaction::TYPE_EXPENSE,
                    $amount,
                    'Withdraw ke rekening '.$request->string('account_number')->toString(),
                );

                return $wallet->refresh();
            });
        } catch (InvalidArgumentException) {
            return $this->errorResponse('Saldo tidak cukup.', 422, [
                'amount' => ['Saldo tidak cukup untuk penarikan.'],
            ]);
        }

        return $this->successResponse([
            'balance' => (int) $wallet->balance,
            'message' => 'Penarikan berhasil diproses.',
        ], 'Penarikan berhasil diproses.');
    }

    public function send(SendMoneyRequest $request): JsonResponse
    {
        try {
            $wallet = DB::transaction(function () use ($request) {
                $senderWallet = $this->walletFor($request->user());
                $recipient = User::where('email', $request->string('recipient_email')->lower())->firstOrFail();
                $recipientWallet = $this->walletFor($recipient);
                $amount = $request->integer('amount');
                $note = $request->input('note');

                $senderWallet->deductBalance($amount);
                $recipientWallet->addBalance($amount);

                $description = 'Kirim uang ke '.$recipient->email.($note ? ': '.$note : '');
                $recipientDescription = 'Terima uang dari '.$request->user()->email.($note ? ': '.$note : '');

                $this->recordTransaction($senderWallet, Transaction::TYPE_EXPENSE, $amount, $description);
                $this->recordTransaction($recipientWallet, Transaction::TYPE_INCOME, $amount, $recipientDescription);

                return $senderWallet->refresh();
            });
        } catch (InvalidArgumentException) {
            return $this->errorResponse('Saldo tidak cukup.', 422, [
                'amount' => ['Saldo tidak cukup untuk mengirim uang.'],
            ]);
        }

        return $this->successResponse([
            'balance' => (int) $wallet->balance,
            'message' => 'Uang berhasil dikirim.',
        ], 'Uang berhasil dikirim.');
    }

    private function walletFor(User $user): Wallet
    {
        return $user->wallet ?: Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'status' => Wallet::STATUS_ACTIVE,
        ]);
    }

    private function recordTransaction(Wallet $wallet, string $type, int $amount, string $description): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $this->walletCategory()->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'source' => Transaction::SOURCE_MANUAL,
            'status' => Transaction::STATUS_COMPLETED,
            'transaction_date' => now(),
        ]);
    }

    private function walletCategory(): Category
    {
        return Category::firstOrCreate(
            ['name' => 'LAINNYA'],
            ['icon' => '📌', 'type' => Category::TYPE_BOTH, 'keywords' => []],
        );
    }

    private function transactions(int $userId): Builder
    {
        return Transaction::query()
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $userId))
            ->where('status', Transaction::STATUS_COMPLETED);
    }
}

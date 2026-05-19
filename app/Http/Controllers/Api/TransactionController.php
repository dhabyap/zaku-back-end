<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatTransactionRequest;
use App\Models\Transaction;
use App\Services\AiTransactionParserService;
use App\Services\DateLabelService;
use App\Services\TransactionParserService;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use ApiResponse;

    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = $this->baseQuery($request)
            ->with('category')
            ->find($id);

        if (! $transaction) {
            return $this->notFoundResponse('Transaction not found.');
        }

        return $this->successResponse([
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => (int) $transaction->amount,
            'description' => $transaction->description,
            'category' => $transaction->category?->name ?? 'LAINNYA',
            'created_at' => $transaction->created_at?->toISOString(),
        ], 'Detail transaksi berhasil diambil');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = $this->baseQuery($request)
            ->with('wallet')
            ->find($id);

        if (! $transaction) {
            return $this->notFoundResponse('Transaction not found.');
        }

        $balance = DB::transaction(function () use ($transaction) {
            $wallet = $transaction->wallet;
            $amount = (float) $transaction->amount;

            if ($transaction->type === Transaction::TYPE_INCOME) {
                $wallet->balance = number_format(((float) $wallet->balance) - $amount, 2, '.', '');
            } else {
                $wallet->balance = number_format(((float) $wallet->balance) + $amount, 2, '.', '');
            }

            $wallet->save();
            $transaction->delete();

            return (int) $wallet->balance;
        });

        return $this->successResponse([
            'id' => $id,
            'balance' => $balance,
        ], 'Transaksi berhasil dihapus');
    }

    public function stats(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);

        return $this->successResponse([
            'total' => (clone $query)->count(),
            'this_month' => (clone $query)
                ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'biggest' => (int) (clone $query)->max('amount'),
            'categories' => (int) (clone $query)
                ->whereNotNull('category_id')
                ->distinct('category_id')
                ->count('category_id'),
        ], 'Statistik transaksi berhasil diambil');
    }

    public function categories(Request $request): JsonResponse
    {
        $totalExpense = (int) $this->baseQuery($request)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->sum('amount');

        if ($totalExpense <= 0) {
            return $this->successResponse([], 'Ringkasan kategori berhasil diambil');
        }

        $categories = $this->baseQuery($request)
            ->with('category')
            ->where('type', Transaction::TYPE_EXPENSE)
            ->get()
            ->groupBy(fn (Transaction $transaction) => $transaction->category?->name ?? 'LAINNYA')
            ->map(function ($transactions, string $name) use ($totalExpense) {
                $amount = (int) $transactions->sum('amount');

                return [
                    'name' => $name,
                    'amount' => $amount,
                    'pct' => (int) round(($amount / $totalExpense) * 100),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();

        return $this->successResponse($categories, 'Ringkasan kategori berhasil diambil');
    }

    public function index(Request $request): JsonResponse
    {
        $filter = strtoupper((string) $request->query('filter', 'SEMUA'));
        $query = $this->baseQuery($request)->with('category');

        if ($filter === 'PEMASUKAN') {
            $query->where('type', Transaction::TYPE_INCOME);
        } elseif ($filter === 'PENGELUARAN') {
            $query->where('type', Transaction::TYPE_EXPENSE);
        } elseif ($filter !== 'SEMUA') {
            $query->whereHas('category', fn (Builder $query) => $query->whereRaw('UPPER(name) = ?', [$filter]));
        }

        $groups = $query->latest('transaction_date')
            ->get()
            ->groupBy(fn (Transaction $transaction) => DateLabelService::month($transaction->transaction_date))
            ->map(fn ($transactions, string $monthLabel) => [
                'month_label' => $monthLabel,
                'transactions' => $transactions->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => (int) $transaction->amount,
                    'type' => $transaction->type,
                    'category_name' => $transaction->category?->name ?? 'LAINNYA',
                    'category_icon' => $transaction->category?->icon ?? '📌',
                    'date_formatted' => DateLabelService::date($transaction->transaction_date),
                    'source' => $transaction->source ?? Transaction::SOURCE_MANUAL,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return $this->successResponse($groups, 'Riwayat transaksi berhasil diambil');
    }

    public function chat(
        ChatTransactionRequest $request,
        TransactionParserService $parser,
        TransactionService $transactions,
    ): JsonResponse {
        $message = $request->string('message')->toString();
        $parsed = $parser->parse($message);

        if ($parsed['amount'] <= 0) {
            return $this->errorResponse('Nominal transaksi tidak ditemukan.', 422, [
                'message' => ['Cantumkan nominal transaksi, contoh: Beli kopi 65 ribu.'],
            ]);
        }

        $transaction = $transactions->create(
            $request->user(),
            $parsed['category'],
            $parsed['type'],
            $parsed['amount'],
            $parsed['description'],
            Transaction::SOURCE_CHAT,
            $message,
        );

        return $this->successResponse([
            'reply_message' => $this->replyMessage($transaction),
            'parsed_data' => [
                'description' => $transaction->description,
                'amount' => (int) $transaction->amount,
                'category' => $transaction->category?->name ?? 'LAINNYA',
                'category_icon' => $transaction->category?->icon ?? '📌',
                'type' => $transaction->type,
            ],
        ], 'Transaksi berhasil dicatat', 201);
    }

    public function aiChat(
        ChatTransactionRequest $request,
        AiTransactionParserService $aiParser,
        TransactionParserService $parser,
        TransactionService $transactions,
    ): JsonResponse {
        $message = $request->string('message')->toString();
        $parsed = $aiParser->parse($message);

        if ($parsed['provider'] === 'local') {
            $localParsed = $parser->parse($message);
            $parsed = [
                'description' => $localParsed['description'],
                'amount' => $localParsed['amount'],
                'category' => $localParsed['category'],
                'type' => $localParsed['type'],
                'response' => null,
                'provider' => 'local',
            ];
        }

        if ($parsed['amount'] === null || $parsed['amount'] <= 0 || $parsed['category'] === null || $parsed['type'] === null) {
            return $this->successResponse([
                'response' => $parsed['response'] ?? 'Aku belum menemukan nominal transaksi. Coba tulis nominalnya, contoh: Beli kopi 65 ribu.',
                'description' => null,
                'amount' => null,
                'amount_formatted' => null,
                'category' => null,
                'type' => null,
            ], 'Pesan berhasil diproses');
        }

        $transaction = $transactions->create(
            $request->user(),
            $parsed['category'],
            $parsed['type'],
            $parsed['amount'],
            $parsed['description'] ?? $message,
            Transaction::SOURCE_CHAT,
            $message,
        );

        return $this->successResponse([
            'response' => $parsed['response'] ?? $this->replyMessage($transaction),
            'description' => $transaction->description,
            'amount' => (int) $transaction->amount,
            'amount_formatted' => $this->formatAmount((int) $transaction->amount, $transaction->type),
            'category' => trim(($transaction->category?->icon ?? 'ðŸ“Œ').' '.($transaction->category?->name ?? 'LAINNYA')),
            'type' => $transaction->type,
        ], 'Transaksi berhasil dicatat');
    }

    private function formatAmount(int $amount, string $type): string
    {
        $prefix = $type === Transaction::TYPE_INCOME ? '+' : '-';

        return $prefix.'Rp '.number_format($amount, 0, ',', '.');
    }

    private function replyMessage(Transaction $transaction): string
    {
        if ($transaction->type === Transaction::TYPE_INCOME) {
            return 'Mantap, pemasukan udah dicatat! '.$transaction->category?->icon;
        }

        return 'Sip, udah dicatat! '.$transaction->category?->icon;
    }

    private function baseQuery(Request $request): Builder
    {
        return Transaction::query()
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->where('status', Transaction::STATUS_COMPLETED);
    }
}

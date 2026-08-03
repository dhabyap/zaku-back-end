<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatTransactionRequest;
use App\Http\Requests\TransactionRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\AiTransactionParserService;
use App\Services\DateLabelService;
use App\Services\TransactionParserService;
use App\Services\TransactionService;
use App\Http\Requests\UpdateTransactionRequest;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use ApiResponse;

    public function store(TransactionRequest $request, TransactionService $transactions): JsonResponse
    {
        $category = Category::where('name', strtoupper($request->string('category')->toString()))->firstOrFail();

        $transaction = $transactions->create(
            $request->user(),
            $category,
            $request->string('type')->toString(),
            $request->integer('amount'),
            $request->string('description')->toString(),
            Transaction::SOURCE_MANUAL,
            null,
            $request->input('transaction_date'),
        );

        ActivityLog::log($transaction, 'created', null, [
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'category' => $category->name,
        ], 'Transaction created', $request);

        return $this->successResponse([
            'id' => $transaction->id,
            'description' => $transaction->description,
            'amount' => (int) $transaction->amount,
            'type' => $transaction->type,
            'category_name' => $transaction->category?->name ?? 'LAINNYA',
            'category_icon' => $transaction->category?->icon ?? 'ðŸ“Œ',
            'date_formatted' => DateLabelService::date($transaction->transaction_date),
            'source' => $transaction->source,
        ], 'Transaksi berhasil dicatat', 201);
    }

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
            $amount = (int) $transaction->amount;

            if ($transaction->type === Transaction::TYPE_INCOME) {
                $wallet->deductBalance($amount);
            } else {
                $wallet->addBalance($amount);
            }

            $transaction->delete();

            return (int) $wallet->balance_cents;
        });

        ActivityLog::log($transaction, 'deleted', [
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ], null, 'Transaction deleted and wallet adjusted', $request);

        return $this->successResponse([
            'id' => $id,
            'balance' => $balance,
        ], 'Transaksi berhasil dihapus');
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->baseQuery($request)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when transaction_date >= ? then 1 end) as this_month", [now()->startOfMonth()])
            ->selectRaw('max(amount) as biggest')
            ->selectRaw('count(distinct category_id) as categories')
            ->first();

        return $this->successResponse([
            'total' => (int) $stats->total,
            'this_month' => (int) $stats->this_month,
            'biggest' => (int) $stats->biggest,
            'categories' => (int) $stats->categories,
        ], 'Statistik transaksi berhasil diambil');
    }

    public function categories(Request $request): JsonResponse
    {
        $month = $request->query('month');

        if ($month === null) {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        } else {
            if (! preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $month)) {
                return $this->errorResponse('Format month harus YYYY-MM', 422, [
                    'month' => ['The month must be in format YYYY-MM.'],
                ]);
            }

            $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        }

        $query = $this->baseQuery($request)
            ->with('category')
            ->where('type', Transaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalExpense = (int) (clone $query)->sum('amount');

        if ($totalExpense <= 0) {
            return $this->successResponse([], 'Ringkasan kategori berhasil diambil');
        }

        $categories = $query->get()
            ->groupBy(fn (Transaction $transaction) => $transaction->category?->name ?? 'LAINNYA')
            ->map(function ($transactions, string $name) use ($totalExpense) {
                $amount = (int) $transactions->sum('amount');
                $count = $transactions->count();

                return [
                    'name' => $name,
                    'icon' => $transactions->first()->category?->icon ?? '📌',
                    'amount' => $amount,
                    'transaction_count' => $count,
                    'percentage' => (int) round(($amount / $totalExpense) * 100),
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
        $limit = max(1, min((int) $request->query('limit', 100), 100));
        $page = max(1, (int) $request->query('page', 1));

        // Search by description
        if ($q = $request->query('q')) {
            $query->where('description', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%');
        }

        // Date range filter
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        // Type filter
        if ($filter === 'PEMASUKAN') {
            $query->where('type', Transaction::TYPE_INCOME);
        } elseif ($filter === 'PENGELUARAN') {
            $query->where('type', Transaction::TYPE_EXPENSE);
        } elseif ($filter !== 'SEMUA') {
            $query->whereHas('category', fn (Builder $query) => $query->whereRaw('UPPER(name) = ?', [$filter]));
        }

        $total = (clone $query)->count();

        // Sorting
        $sortBy = $request->query('sort_by', 'transaction_date');
        $sortOrder = strtolower($request->query('sort_order', 'desc'));

        $allowedSorts = ['transaction_date', 'amount', 'description', 'type'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'transaction_date';
        }
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $transactions = $query->orderBy($sortBy, $sortOrder)
            ->forPage($page, $limit)
            ->get()
            ->values();

        $groups = $transactions
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

        return $this->successResponse([
            'groups' => $groups,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'has_more' => ($page * $limit) < $total,
            ],
        ], 'Riwayat transaksi berhasil diambil');
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

    public function update(UpdateTransactionRequest $request, int $id, TransactionService $transactions): JsonResponse
    {
        $transaction = $this->baseQuery($request)
            ->with('wallet', 'category')
            ->find($id);

        if (! $transaction) {
            return $this->notFoundResponse('Transaction not found.');
        }

        $category = null;
        if ($request->has('category')) {
            $category = Category::where('name', strtoupper($request->string('category')->toString()))->firstOrFail();
        }

        $updated = $transactions->update(
            $transaction,
            $category,
            $request->input('type'),
            $request->input('amount'),
            $request->input('description'),
            $request->input('transaction_date'),
        );

        ActivityLog::log($transaction, 'updated', [
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ], [
            'type' => $updated->type,
            'amount' => $updated->amount,
            'description' => $updated->description,
            'category' => $updated->category?->name,
        ], 'Transaction updated', $request);

        return $this->successResponse([
            'id' => $updated->id,
            'description' => $updated->description,
            'amount' => (int) $updated->amount,
            'type' => $updated->type,
            'category_name' => $updated->category?->name ?? 'LAINNYA',
            'category_icon' => $updated->category?->icon ?? 'ðŸ“Œ',
            'date_formatted' => DateLabelService::date($updated->transaction_date),
            'source' => $updated->source,
        ], 'Transaksi berhasil diperbarui');
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

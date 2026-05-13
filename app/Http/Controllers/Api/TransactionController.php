<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatTransactionRequest;
use App\Models\Transaction;
use App\Services\DateLabelService;
use App\Services\TransactionParserService;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filter = strtoupper((string) $request->query('filter', 'SEMUA'));
        $query = Transaction::query()
            ->with('category')
            ->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->where('status', Transaction::STATUS_COMPLETED);

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

    private function replyMessage(Transaction $transaction): string
    {
        if ($transaction->type === Transaction::TYPE_INCOME) {
            return 'Mantap, pemasukan udah dicatat! '.$transaction->category?->icon;
        }

        return 'Sip, udah dicatat! '.$transaction->category?->icon;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use App\Models\Wallet;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $recurrings = RecurringTransaction::where('user_id', $request->user()->id)
            ->with('category')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('next_execution_date')
            ->paginate($request->per_page ?? 20);

        return $this->successResponse(
            RecurringTransactionResource::collection($recurrings),
            'Daftar transaksi berulang'
        );
    }

    public function store(StoreRecurringTransactionRequest $request): JsonResponse
    {
        $user = $request->user();
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance_cents' => 0, 'status' => Wallet::STATUS_ACTIVE],
        );

        $recurring = RecurringTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $request->category_id,
            'type' => $request->type,
            'amount_cents' => $request->amount_cents,
            'description' => $request->description,
            'frequency' => $request->frequency,
            'interval_value' => $request->interval_value ?? 1,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'next_execution_date' => $request->start_date,
            'status' => RecurringTransaction::STATUS_ACTIVE,
        ]);

        return $this->successResponse(
            new RecurringTransactionResource($recurring->load('category')),
            'Transaksi berulang berhasil dibuat',
            201,
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringTransaction::where('user_id', $request->user()->id)
            ->with('category')
            ->findOrFail($id);

        return $this->successResponse(
            new RecurringTransactionResource($recurring),
        );
    }

    public function update(UpdateRecurringTransactionRequest $request, int $id): JsonResponse
    {
        $recurring = RecurringTransaction::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = array_filter($request->only([
            'type', 'amount_cents', 'description', 'category_id',
            'frequency', 'interval_value', 'start_date', 'end_date', 'status',
        ]), fn ($v) => $v !== null);

        if (isset($data['status']) && $data['status'] === RecurringTransaction::STATUS_ACTIVE
            && $recurring->status !== RecurringTransaction::STATUS_ACTIVE) {
            $data['next_execution_date'] = $data['start_date'] ?? $recurring->start_date;
        }

        $recurring->update($data);

        return $this->successResponse(
            new RecurringTransactionResource($recurring->fresh()->load('category')),
            'Transaksi berulang berhasil diperbarui',
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringTransaction::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $recurring->update(['status' => RecurringTransaction::STATUS_CANCELLED]);
        $recurring->delete();

        return $this->successResponse(null, 'Transaksi berulang dibatalkan');
    }
}

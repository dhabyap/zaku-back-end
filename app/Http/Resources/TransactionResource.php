<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'reference_id' => $this->reference_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

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
            'description' => $this->description,
            'amount' => (int) $this->amount,
            'type' => $this->type,
            'category_name' => $this->category?->name ?? 'LAINNYA',
            'category_icon' => $this->category?->icon ?? '📌',
            'date_formatted' => $this->transaction_date?->translatedFormat('j F Y'),
            'source' => $this->source ?? 'manual',
        ];
    }
}

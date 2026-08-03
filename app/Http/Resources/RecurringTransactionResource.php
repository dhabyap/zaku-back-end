<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount_cents' => $this->amount_cents,
            'amount_formatted' => 'Rp '.number_format($this->amount_cents, 0, ',', '.'),
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon,
            ]),
            'frequency' => $this->frequency,
            'interval_value' => $this->interval_value,
            'frequency_label' => $this->interval_value > 1
                ? "Setiap {$this->interval_value} {$this->frequency}"
                : ucfirst($this->frequency),
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'next_execution_date' => $this->next_execution_date->format('Y-m-d'),
            'last_executed_at' => $this->last_executed_at?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

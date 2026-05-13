<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'email' => $this->email,
            'avatar_initial' => strtoupper(substr((string) $this->full_name, 0, 1)),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
        ];
    }
}

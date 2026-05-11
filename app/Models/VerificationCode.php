<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->is_used || $this->used_at !== null;
    }

    public function markAsUsed(): bool
    {
        $this->forceFill([
            'is_used' => true,
            'used_at' => now(),
        ]);

        return $this->save();
    }

    public static function generateCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false)->whereNull('used_at');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->unused()->where('expires_at', '>', Carbon::now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Wallet extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function addBalance(float|int|string $amount): bool
    {
        $this->assertPositiveAmount($amount);
        $this->balance = number_format(((float) $this->balance) + ((float) $amount), 2, '.', '');

        return $this->save();
    }

    public function deductBalance(float|int|string $amount): bool
    {
        $this->assertPositiveAmount($amount);

        if (((float) $this->balance) < ((float) $amount)) {
            throw new InvalidArgumentException('Insufficient wallet balance.');
        }

        $this->balance = number_format(((float) $this->balance) - ((float) $amount), 2, '.', '');

        return $this->save();
    }

    public function getAvailableBalance(): string
    {
        return (string) $this->balance;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    private function assertPositiveAmount(float|int|string $amount): void
    {
        if (! is_numeric($amount) || ((float) $amount) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
    }
}

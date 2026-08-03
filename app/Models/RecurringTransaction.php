<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount_cents',
        'description',
        'frequency',
        'interval_value',
        'start_date',
        'end_date',
        'next_execution_date',
        'last_executed_at',
        'status',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'next_execution_date' => 'date:Y-m-d',
        'last_executed_at' => 'date:Y-m-d',
        'interval_value' => 'integer',
    ];

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('source', Transaction::SOURCE_RECURRING);
    }

    public function isDue(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->next_execution_date->isPast()
            && ($this->end_date === null || $this->next_execution_date->lte($this->end_date));
    }

    public function calculateNextDate(): \Carbon\Carbon
    {
        return match ($this->frequency) {
            'daily' => $this->next_execution_date->addDays($this->interval_value),
            'weekly' => $this->next_execution_date->addWeeks($this->interval_value),
            'monthly' => $this->next_execution_date->addMonths($this->interval_value),
            'yearly' => $this->next_execution_date->addYears($this->interval_value),
        };
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDue(Builder $query): void
    {
        $query->active()
            ->where('next_execution_date', '<=', now()->toDateString())
            ->where(function (Builder $q) {
                $q->whereNull('end_date')
                  ->orWhere('next_execution_date', '<=', $q->qualifyColumn('end_date'));
            });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loggable_id',
        'loggable_type',
        'event',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        Model $loggable,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?\Illuminate\Http\Request $request = null,
    ): self {
        return static::create([
            'user_id' => $request?->user()?->id,
            'loggable_id' => $loggable->id,
            'loggable_type' => get_class($loggable),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Log page view (tanpa loggable entity).
     */
    public static function logPageView(string $page, $user = null): void
    {
        static::create([
            'user_id' => $user?->id ?? request()?->user()?->id,
            'loggable_id' => 0,
            'loggable_type' => 'page_view',
            'event' => 'page_view',
            'old_values' => null,
            'new_values' => ['page' => $page],
            'description' => "User viewed {$page}",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Log feature usage.
     */
    public static function logFeature(string $feature, array $data = [], $user = null): void
    {
        static::create([
            'user_id' => $user?->id ?? request()?->user()?->id,
            'loggable_id' => 0,
            'loggable_type' => 'feature_used',
            'event' => 'feature_used',
            'old_values' => null,
            'new_values' => array_merge(['feature' => $feature], $data),
            'description' => "Feature used: {$feature}",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Get analytics summary.
     */
    public static function getAnalytics(?string $type = null, int $days = 30): array
    {
        $start = now()->subDays($days);
        $query = static::where('created_at', '>=', $start);

        if ($type) {
            $query->where('event', $type);
        }

        $total = $query->count();

        $byDay = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byPage = (clone $query)
            ->where('event', 'page_view')
            ->selectRaw("JSON_EXTRACT(new_values, '$.page') as page, COUNT(*) as count")
            ->groupBy('page')
            ->orderByDesc('count')
            ->get();

        return [
            'total' => $total,
            'by_day' => $byDay,
            'by_page' => $byPage,
        ];
    }
}

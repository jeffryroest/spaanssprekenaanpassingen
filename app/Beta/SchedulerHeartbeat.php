<?php

namespace App\Beta;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class SchedulerHeartbeat
{
    private const CACHE_KEY = 'operations.scheduler.last_seen_at';

    public function record(): void
    {
        Cache::put(self::CACHE_KEY, CarbonImmutable::now()->toIso8601String(), now()->addHours(2));
    }

    public function lastSeenAt(): ?CarbonImmutable
    {
        $value = Cache::get(self::CACHE_KEY);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    public function isFresh(int $minutes = 5): bool
    {
        return $this->lastSeenAt()?->greaterThanOrEqualTo(now()->subMinutes($minutes)) ?? false;
    }
}

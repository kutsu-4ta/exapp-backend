<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Stopwatch\StopwatchRepositoryInterface;
use App\Models\Stopwatch;

class EloquentStopwatchRepository implements StopwatchRepositoryInterface
{
    public function findOrCreate(int $userId, string $sessionKey = 'default'): Stopwatch
    {
        return Stopwatch::firstOrCreate(
            ['user_id' => $userId, 'session_key' => $sessionKey],
            ['is_running' => false, 'started_at' => null, 'elapsed_seconds' => 0],
        );
    }

    public function save(Stopwatch $stopwatch): Stopwatch
    {
        $stopwatch->save();

        return $stopwatch;
    }
}

<?php

namespace App\UseCases\Stopwatch;

use App\Domain\Stopwatch\StopwatchRepositoryInterface;
use Illuminate\Support\Carbon;

class GetStopwatchUseCase
{
    public function __construct(
        private readonly StopwatchRepositoryInterface $repository,
    ) {}

    /** @return array{isRunning: bool, elapsedSeconds: int} */
    public function __invoke(int $userId, string $sessionKey = 'default'): array
    {
        $sw = $this->repository->findOrCreate($userId, $sessionKey);

        $elapsed = $sw->elapsed_seconds;

        if ($sw->is_running && $sw->started_at !== null) {
            $elapsed += (int) $sw->started_at->diffInSeconds(Carbon::now());
        }

        return [
            'isRunning'      => $sw->is_running,
            'elapsedSeconds' => $elapsed,
        ];
    }
}

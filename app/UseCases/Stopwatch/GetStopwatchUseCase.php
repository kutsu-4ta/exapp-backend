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
            $diff = (int) $sw->started_at->diffInSeconds(Carbon::now());

            // サーバー時刻ずれ等による異常値（24時間超）はリセットして0を返す
            if ($diff > 86400) {
                $sw->is_running      = false;
                $sw->started_at      = null;
                $sw->elapsed_seconds = 0;
                $this->repository->save($sw);

                return ['isRunning' => false, 'elapsedSeconds' => 0];
            }

            $elapsed += $diff;
        }

        return [
            'isRunning'      => $sw->is_running,
            'elapsedSeconds' => $elapsed,
        ];
    }
}

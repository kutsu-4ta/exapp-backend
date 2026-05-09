<?php

namespace App\UseCases\Stopwatch;

use App\Domain\Stopwatch\StopwatchRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StopStopwatchUseCase
{
    public function __construct(
        private readonly StopwatchRepositoryInterface $repository,
    ) {}

    /** @return array{isRunning: bool, elapsedSeconds: int} */
    public function __invoke(int $userId, string $sessionKey = 'default'): array
    {
        return DB::transaction(function () use ($userId, $sessionKey) {
            $sw = $this->repository->findOrCreate($userId, $sessionKey);

            // 停止済み、または started_at が null の場合はそのまま返す
            if (!$sw->is_running || $sw->started_at === null) {
                return [
                    'isRunning'      => false,
                    'elapsedSeconds' => $sw->elapsed_seconds,
                ];
            }

            $diff = (int) $sw->started_at->diffInSeconds(Carbon::now());
            if ($diff <= 86400) {
                $sw->elapsed_seconds += $diff;
            }
            $sw->is_running  = false;
            $sw->started_at  = null;

            $this->repository->save($sw);

            return [
                'isRunning'      => $sw->is_running,
                'elapsedSeconds' => $sw->elapsed_seconds,
            ];
        });
    }
}

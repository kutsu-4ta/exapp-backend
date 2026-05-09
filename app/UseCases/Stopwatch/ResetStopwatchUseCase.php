<?php

namespace App\UseCases\Stopwatch;

use App\Domain\Stopwatch\StopwatchRepositoryInterface;

class ResetStopwatchUseCase
{
    public function __construct(
        private readonly StopwatchRepositoryInterface $repository,
    ) {}

    /** @return array{isRunning: bool, elapsedSeconds: int} */
    public function __invoke(int $userId, string $sessionKey = 'default'): array
    {
        $sw = $this->repository->findOrCreate($userId, $sessionKey);

        $sw->is_running      = false;
        $sw->started_at      = null;
        $sw->elapsed_seconds = 0;

        $this->repository->save($sw);

        return ['isRunning' => false, 'elapsedSeconds' => 0];
    }
}

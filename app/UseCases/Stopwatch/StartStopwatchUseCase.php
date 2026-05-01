<?php

namespace App\UseCases\Stopwatch;

use App\Domain\Stopwatch\StopwatchRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StartStopwatchUseCase
{
    public function __construct(
        private readonly StopwatchRepositoryInterface $repository,
    ) {}

    /** @return array{isRunning: bool, elapsedSeconds: int} */
    public function __invoke(int $userId, string $sessionKey = 'default'): array
    {
        return DB::transaction(function () use ($userId, $sessionKey) {
            $sw = $this->repository->findOrCreate($userId, $sessionKey);

            if ($sw->is_running) {
                throw new ConflictHttpException('ストップウォッチはすでに計測中です。');
            }

            $sw->is_running  = true;
            $sw->started_at  = Carbon::now();

            $this->repository->save($sw);

            return [
                'isRunning'      => $sw->is_running,
                'elapsedSeconds' => $sw->elapsed_seconds,
            ];
        });
    }
}

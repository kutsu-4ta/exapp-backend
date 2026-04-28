<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Models\DailyLog;

class CompleteDailyLogUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $date): DailyLog
    {
        $dailyLog = $this->repository->findByDate($userId, $date);

        if ($dailyLog === null) {
            abort(404);
        }

        return $this->repository->complete($dailyLog);
    }
}

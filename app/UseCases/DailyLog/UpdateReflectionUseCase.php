<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Models\DailyLog;

class UpdateReflectionUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $date, ?string $reflection): DailyLog
    {
        $dailyLog = $this->repository->findByDate($userId, $date);

        if ($dailyLog === null) {
            abort(404);
        }

        return $this->repository->updateReflection($dailyLog, $reflection);
    }
}

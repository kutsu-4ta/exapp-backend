<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;

class DeleteDailyLogUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $date): void
    {
        $dailyLog = $this->repository->findByDate($userId, $date);

        if ($dailyLog === null) {
            abort(404);
        }

        $this->repository->delete($dailyLog);
    }
}

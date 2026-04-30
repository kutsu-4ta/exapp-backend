<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Models\DailyLog;

class CreateDailyLogUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $date): DailyLog
    {
        return $this->repository->create($userId, $date);
    }
}

<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use Illuminate\Support\Collection;

class ListDailyLogsUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $year, int $month): Collection
    {
        return $this->repository->findByMonth($userId, $year, $month);
    }
}

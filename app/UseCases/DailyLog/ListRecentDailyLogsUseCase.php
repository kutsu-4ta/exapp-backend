<?php

namespace App\UseCases\DailyLog;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use Illuminate\Support\Collection;

class ListRecentDailyLogsUseCase
{
    public function __construct(
        private readonly DailyLogRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $limit, ?string $before): Collection
    {
        return $this->repository->findRecent($userId, $limit, $before);
    }
}

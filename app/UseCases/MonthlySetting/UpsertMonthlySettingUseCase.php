<?php

namespace App\UseCases\MonthlySetting;

use App\Domain\MonthlySetting\MonthlySettingRepositoryInterface;
use App\Models\MonthlySetting;

class UpsertMonthlySettingUseCase
{
    public function __construct(
        private readonly MonthlySettingRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $year, int $month, array $data): MonthlySetting
    {
        return $this->repository->upsert($userId, $year, $month, $data);
    }
}

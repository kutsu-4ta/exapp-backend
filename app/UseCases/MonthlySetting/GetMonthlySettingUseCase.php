<?php

namespace App\UseCases\MonthlySetting;

use App\Domain\MonthlySetting\MonthlySettingRepositoryInterface;

class GetMonthlySettingUseCase
{
    public function __construct(
        private readonly MonthlySettingRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $year, int $month): array
    {
        $setting = $this->repository->findByYearMonth($userId, $year, $month);

        return [
            'year' => $year,
            'month' => $month,
            'targetMin' => $setting?->target_min ?? 0.0,
            'targetMax' => $setting?->target_max ?? 0.0,
        ];
    }
}

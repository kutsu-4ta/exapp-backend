<?php

namespace App\UseCases\AlertSetting;

use App\Domain\AlertSetting\AlertSettingRepositoryInterface;
use App\Models\AlertSetting;

class UpsertAlertSettingUseCase
{
    public function __construct(
        private readonly AlertSettingRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $thresholdDays, bool $includeUntouched): AlertSetting
    {
        return $this->repository->upsert($userId, $thresholdDays, $includeUntouched);
    }
}

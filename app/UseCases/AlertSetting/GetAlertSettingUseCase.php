<?php

namespace App\UseCases\AlertSetting;

use App\Domain\AlertSetting\AlertSettingRepositoryInterface;
use App\Models\AlertSetting;

class GetAlertSettingUseCase
{
    private const DEFAULT_THRESHOLD_DAYS = 7;
    private const DEFAULT_INCLUDE_UNTOUCHED = false;

    public function __construct(
        private readonly AlertSettingRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId): AlertSetting
    {
        $setting = $this->repository->findByUser($userId);

        if ($setting === null) {
            $setting = new AlertSetting([
                'threshold_days'    => self::DEFAULT_THRESHOLD_DAYS,
                'include_untouched' => self::DEFAULT_INCLUDE_UNTOUCHED,
            ]);
        }

        return $setting;
    }
}

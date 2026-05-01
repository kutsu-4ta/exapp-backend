<?php

namespace App\Infrastructure\Repositories;

use App\Domain\AlertSetting\AlertSettingRepositoryInterface;
use App\Models\AlertSetting;

class EloquentAlertSettingRepository implements AlertSettingRepositoryInterface
{
    public function findByUser(int $userId): ?AlertSetting
    {
        return AlertSetting::where('user_id', $userId)->first();
    }

    public function upsert(int $userId, int $thresholdDays, bool $includeUntouched): AlertSetting
    {
        $setting = AlertSetting::updateOrCreate(
            ['user_id' => $userId],
            ['threshold_days' => $thresholdDays, 'include_untouched' => $includeUntouched],
        );

        return $setting;
    }
}

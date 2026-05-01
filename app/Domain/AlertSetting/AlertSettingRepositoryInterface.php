<?php

namespace App\Domain\AlertSetting;

use App\Models\AlertSetting;

interface AlertSettingRepositoryInterface
{
    public function findByUser(int $userId): ?AlertSetting;

    public function upsert(int $userId, int $thresholdDays, bool $includeUntouched): AlertSetting;
}

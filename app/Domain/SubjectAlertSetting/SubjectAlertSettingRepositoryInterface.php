<?php

namespace App\Domain\SubjectAlertSetting;

use App\Models\SubjectAlertSetting;

interface SubjectAlertSettingRepositoryInterface
{
    public function findByUserAndSubject(int $userId, int $subjectId): ?SubjectAlertSetting;

    public function upsert(
        int $userId,
        int $subjectId,
        bool $touchAlertEnabled,
        int $thresholdDays,
        bool $includeUntouched,
        bool $minutesAlertEnabled,
        int $minutesThresholdDays,
        int $minutesThreshold,
    ): SubjectAlertSetting;
}

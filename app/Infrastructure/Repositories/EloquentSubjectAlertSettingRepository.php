<?php

namespace App\Infrastructure\Repositories;

use App\Domain\SubjectAlertSetting\SubjectAlertSettingRepositoryInterface;
use App\Models\SubjectAlertSetting;

class EloquentSubjectAlertSettingRepository implements SubjectAlertSettingRepositoryInterface
{
    public function findByUserAndSubject(int $userId, int $subjectId): ?SubjectAlertSetting
    {
        return SubjectAlertSetting::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    public function upsert(
        int $userId,
        int $subjectId,
        bool $touchAlertEnabled,
        int $thresholdDays,
        bool $includeUntouched,
        bool $minutesAlertEnabled,
        int $minutesThresholdDays,
        int $minutesThreshold,
    ): SubjectAlertSetting {
        return SubjectAlertSetting::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            [
                'touch_alert_enabled'    => $touchAlertEnabled,
                'threshold_days'         => $thresholdDays,
                'include_untouched'      => $includeUntouched,
                'minutes_alert_enabled'  => $minutesAlertEnabled,
                'minutes_threshold_days' => $minutesThresholdDays,
                'minutes_threshold'      => $minutesThreshold,
            ],
        );
    }
}

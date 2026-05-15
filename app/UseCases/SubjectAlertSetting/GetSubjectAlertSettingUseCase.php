<?php

namespace App\UseCases\SubjectAlertSetting;

use App\Domain\SubjectAlertSetting\SubjectAlertSettingRepositoryInterface;
use App\Models\Subject;
use App\Models\SubjectAlertSetting;

class GetSubjectAlertSettingUseCase
{
    private const DEFAULT_TOUCH_ALERT_ENABLED    = true;
    private const DEFAULT_THRESHOLD_DAYS         = 7;
    private const DEFAULT_INCLUDE_UNTOUCHED      = false;
    private const DEFAULT_MINUTES_ALERT_ENABLED  = false;
    private const DEFAULT_MINUTES_THRESHOLD_DAYS = 7;
    private const DEFAULT_MINUTES_THRESHOLD      = 60;

    public function __construct(
        private readonly SubjectAlertSettingRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $subjectName): SubjectAlertSetting
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $setting = $this->repository->findByUserAndSubject($userId, $subject->id);

        if ($setting === null) {
            $setting = new SubjectAlertSetting([
                'touch_alert_enabled'    => self::DEFAULT_TOUCH_ALERT_ENABLED,
                'threshold_days'         => self::DEFAULT_THRESHOLD_DAYS,
                'include_untouched'      => self::DEFAULT_INCLUDE_UNTOUCHED,
                'minutes_alert_enabled'  => self::DEFAULT_MINUTES_ALERT_ENABLED,
                'minutes_threshold_days' => self::DEFAULT_MINUTES_THRESHOLD_DAYS,
                'minutes_threshold'      => self::DEFAULT_MINUTES_THRESHOLD,
            ]);
        }

        return $setting;
    }
}

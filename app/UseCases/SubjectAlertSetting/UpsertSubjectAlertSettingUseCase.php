<?php

namespace App\UseCases\SubjectAlertSetting;

use App\Domain\SubjectAlertSetting\SubjectAlertSettingRepositoryInterface;
use App\Models\Subject;
use App\Models\SubjectAlertSetting;

class UpsertSubjectAlertSettingUseCase
{
    public function __construct(
        private readonly SubjectAlertSettingRepositoryInterface $repository,
    ) {}

    public function __invoke(
        int $userId,
        string $subjectName,
        bool $touchAlertEnabled,
        int $thresholdDays,
        bool $includeUntouched,
        bool $minutesAlertEnabled,
        int $minutesThresholdDays,
        int $minutesThreshold,
    ): SubjectAlertSetting {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        return $this->repository->upsert(
            $userId,
            $subject->id,
            $touchAlertEnabled,
            $thresholdDays,
            $includeUntouched,
            $minutesAlertEnabled,
            $minutesThresholdDays,
            $minutesThreshold,
        );
    }
}

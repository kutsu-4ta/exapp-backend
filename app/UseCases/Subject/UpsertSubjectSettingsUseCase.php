<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectSetting;

class UpsertSubjectSettingsUseCase
{
    public function __invoke(int $userId, string $subjectName, ?string $finalTarget, ?string $themeColor): array
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $setting = SubjectSetting::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subject->id],
            ['final_target' => $finalTarget, 'theme_color' => $themeColor],
        );

        return [
            'finalTarget' => $setting->final_target,
            'themeColor'  => $setting->theme_color,
        ];
    }
}

<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectSetting;

class GetSubjectSettingsUseCase
{
    public function __invoke(int $userId, string $subjectName): array
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $setting = SubjectSetting::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->first();

        return [
            'finalTarget' => $setting?->final_target,
            'themeColor'  => $setting?->theme_color,
        ];
    }
}

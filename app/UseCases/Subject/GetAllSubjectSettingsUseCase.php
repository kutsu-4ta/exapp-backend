<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectSetting;
use Illuminate\Support\Collection;

class GetAllSubjectSettingsUseCase
{
    public function __invoke(int $userId): Collection
    {
        $subjects = Subject::where('user_id', $userId)
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get();

        $settings = SubjectSetting::where('user_id', $userId)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy('subject_id');

        return $subjects->map(function (Subject $subject) use ($settings) {
            $setting = $settings->get($subject->id);
            return [
                'subject'     => $subject->name,
                'finalTarget' => $setting?->final_target,
                'themeColor'  => $setting?->theme_color,
            ];
        });
    }
}

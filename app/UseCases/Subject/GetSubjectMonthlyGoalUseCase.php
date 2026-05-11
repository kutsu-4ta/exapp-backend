<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;

class GetSubjectMonthlyGoalUseCase
{
    public function __invoke(int $userId, string $subjectName, int $year, int $month): array
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $goalRecord = SubjectMonthlyGoal::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return [
            'year'  => $year,
            'month' => $month,
            'goal'  => $goalRecord?->goal,
        ];
    }
}

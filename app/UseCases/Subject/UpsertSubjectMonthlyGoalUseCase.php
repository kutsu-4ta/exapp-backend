<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;

class UpsertSubjectMonthlyGoalUseCase
{
    public function __invoke(int $userId, string $subjectName, int $year, int $month, ?string $goal): array
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $record = SubjectMonthlyGoal::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subject->id, 'year' => $year, 'month' => $month],
            ['goal' => $goal],
        );

        return [
            'year'  => $record->year,
            'month' => $record->month,
            'goal'  => $record->goal,
        ];
    }
}

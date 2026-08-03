<?php

namespace App\UseCases\Subject;

use App\Models\Problem;
use App\Models\StudyTicket;
use App\Models\Subject;
use Illuminate\Support\Collection;

class GetSubjectCountsUseCase
{
    public function __invoke(int $userId): Collection
    {
        $subjects = Subject::where('user_id', $userId)
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get();

        $subjectIds = $subjects->pluck('id');

        $problemCounts = Problem::where('user_id', $userId)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('subject_id, count(*) as cnt')
            ->groupBy('subject_id')
            ->pluck('cnt', 'subject_id');

        $ticketCounts = StudyTicket::where('user_id', $userId)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('subject_id, count(*) as cnt')
            ->groupBy('subject_id')
            ->pluck('cnt', 'subject_id');

        return $subjects->map(function (Subject $subject) use ($problemCounts, $ticketCounts) {
            return [
                'subject'      => $subject->name,
                'problemCount' => (int) ($problemCounts->get($subject->id, 0)),
                'ticketCount'  => (int) ($ticketCounts->get($subject->id, 0)),
            ];
        });
    }
}

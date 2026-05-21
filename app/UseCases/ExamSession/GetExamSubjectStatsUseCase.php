<?php

namespace App\UseCases\ExamSession;

use App\Enums\Rank;
use App\Infrastructure\Repositories\EloquentExamSessionRepository;
use App\Models\Subject;

class GetExamSubjectStatsUseCase
{
    public function __construct(
        private readonly EloquentExamSessionRepository $repository,
    ) {}

    public function __invoke(int $userId, string $subjectName, ?int $year = null, ?int $month = null): array
    {
        $subject = Subject::where('user_id', $userId)->where('name', $subjectName)->firstOrFail();

        $sessions = $this->repository->findCompletedBySubject($userId, $subject->id, $year, $month);

        $sessionCount = $sessions->count();

        $totalScore = $sessionCount > 0 ? $sessions->avg(fn ($s) => $s->totalScore()) : 0.0;
        $pureScore = $sessionCount > 0 ? $sessions->avg(fn ($s) => $s->pureScore()) : 0.0;

        $allQuestions = $sessions->flatMap(fn ($s) => $s->questions);

        $rankStats = collect(Rank::cases())->map(function (Rank $rank) use ($allQuestions) {
            $rankQuestions = $allQuestions->filter(fn ($q) => $q->rank === $rank && $q->is_correct !== null);
            $count = $rankQuestions->count();
            $correctRate = $count > 0 ? $rankQuestions->where('is_correct', true)->count() / $count : 0.0;

            return [
                'rank' => $rank->value,
                'correctRate' => round($correctRate, 4),
                'count' => $count,
            ];
        })->values()->toArray();

        $recentMistakes = $sessions
            ->sortByDesc('completed_at')
            ->flatMap(fn ($s) => $s->questions->filter(fn ($q) => filled($q->note) && !$q->has_children)->map(fn ($q) => [
                'questionId' => $q->id,
                'sessionId' => $s->id,
                'examYear' => $s->exam_year,
                'displayId' => $q->display_id,
                'rank' => $q->rank?->value,
                'note' => $q->note,
                'isDoubtful' => $q->is_doubtful,
                'completedAt' => $s->completed_at?->toIso8601String(),
            ]))
            ->take(10)
            ->values()
            ->toArray();

        return [
            'subject' => $subject->name,
            'sessionCount' => $sessionCount,
            'avgTotalScore' => round((float) $totalScore, 2),
            'avgPureScore' => round((float) $pureScore, 2),
            'rankStats' => $rankStats,
            'recentMistakes' => $recentMistakes,
        ];
    }
}

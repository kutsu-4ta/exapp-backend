<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Models\ExamSession;
use App\Models\Subject;

class CompleteExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId, ?string $subjectName, string $examYear, array $questions): ExamSession
    {
        $session = $this->repository->findByIdAndUser($id, $userId);

        if ($session === null) {
            abort(404);
        }

        $session->subject_id = $subjectName !== null
            ? Subject::firstOrCreate(
                ['user_id' => $userId, 'name' => $subjectName],
                ['display_order' => (Subject::where('user_id', $userId)->max('display_order') ?? -1) + 1],
            )->id
            : null;
        $session->exam_year = $examYear;

        $rows = array_map(fn (array $q) => [
            'sort_order' => $q['sortOrder'],
            'display_id' => $q['displayId'],
            'is_sub' => $q['isSub'],
            'has_children' => $q['hasChildren'],
            'rank' => $q['rank'],
            'my_answer' => $q['myAnswer'] ?? null,
            'is_correct' => $q['isCorrect'] ?? null,
            'is_doubtful' => $q['isDoubtful'],
            'point' => $q['point'],
            'note' => $q['note'] ?? null,
            'answered_time_ms' => $q['answeredTimeMs'] ?? null,
        ], $questions);

        return $this->repository->complete($session, $rows);
    }
}

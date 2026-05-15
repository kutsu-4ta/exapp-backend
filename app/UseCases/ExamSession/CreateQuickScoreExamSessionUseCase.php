<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\ExamSession;

class CreateQuickScoreExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(
        int $userId,
        string $subjectName,
        string $examYear,
        int $totalScore,
        ?int $pureScore,
    ): ExamSession {
        $subjectId = $this->subjectRepository->firstOrCreate($userId, $subjectName)->id;

        return $this->repository->createCompleted($userId, [
            'subject_id'  => $subjectId,
            'exam_year'   => $examYear,
            'total_score' => $totalScore,
            'pure_score'  => $pureScore ?? $totalScore,
        ]);
    }
}

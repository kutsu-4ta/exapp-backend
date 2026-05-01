<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\ExamSession;

class CreateExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, ?string $subjectName, string $examYear): ExamSession
    {
        $subjectId = null;
        if ($subjectName !== null) {
            $subjectId = $this->subjectRepository->firstOrCreate($userId, $subjectName)->id;
        }

        return $this->repository->create($userId, [
            'subject_id' => $subjectId,
            'exam_year'  => $examYear,
        ]);
    }
}

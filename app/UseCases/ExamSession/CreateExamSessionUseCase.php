<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Models\ExamSession;
use App\Models\Subject;

class CreateExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $subjectName, string $examYear): ExamSession
    {
        $subject = Subject::where('name', $subjectName)->firstOrFail();

        return $this->repository->create($userId, [
            'subject_id' => $subject->id,
            'exam_year' => $examYear,
        ]);
    }
}

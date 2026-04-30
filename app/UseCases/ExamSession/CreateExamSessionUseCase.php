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

    public function __invoke(int $userId, ?string $subjectName, string $examYear): ExamSession
    {
        $subjectId = null;
        if ($subjectName !== null) {
            $subjectId = Subject::firstOrCreate(['name' => $subjectName])->id;
        }

        return $this->repository->create($userId, [
            'subject_id' => $subjectId,
            'exam_year' => $examYear,
        ]);
    }
}

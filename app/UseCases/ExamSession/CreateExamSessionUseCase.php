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
            $subjectId = Subject::firstOrCreate(
                ['user_id' => $userId, 'name' => $subjectName],
                ['display_order' => (Subject::where('user_id', $userId)->max('display_order') ?? -1) + 1],
            )->id;
        }

        return $this->repository->create($userId, [
            'subject_id' => $subjectId,
            'exam_year' => $examYear,
        ]);
    }
}

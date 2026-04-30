<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Models\ExamSession;
use App\Models\Subject;

class UpdateExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId, array $data): ExamSession
    {
        $session = $this->repository->findByIdAndUser($id, $userId);

        if ($session === null) {
            abort(404);
        }

        $updateData = [];

        if (isset($data['subject'])) {
            $subject = Subject::where('name', $data['subject'])->firstOrFail();
            $updateData['subject_id'] = $subject->id;
        }

        if (isset($data['exam_year'])) {
            $updateData['exam_year'] = $data['exam_year'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $this->repository->update($session, $updateData);
    }
}

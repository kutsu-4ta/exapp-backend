<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\ExamSession;

class UpdateExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $id, int $userId, array $data): ExamSession
    {
        $session = $this->repository->findByIdAndUser($id, $userId);

        if ($session === null) {
            abort(404);
        }

        $updateData = [];

        if (array_key_exists('subject', $data)) {
            $updateData['subject_id'] = $data['subject'] !== null
                ? $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id
                : null;
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

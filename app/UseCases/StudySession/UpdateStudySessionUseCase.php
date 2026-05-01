<?php

namespace App\UseCases\StudySession;

use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\StudySession;

class UpdateStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, int $sessionId, array $data): StudySession
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            abort(404);
        }

        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->repository->update($session, array_merge(
            array_diff_key($data, ['subject' => null]),
            ['subject_id' => $subjectId],
        ));
    }
}

<?php

namespace App\UseCases\StudySession;

use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Models\StudySession;

class UpdateStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $sessionId, array $data): StudySession
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            abort(404);
        }

        return $this->repository->update($session, $data);
    }
}

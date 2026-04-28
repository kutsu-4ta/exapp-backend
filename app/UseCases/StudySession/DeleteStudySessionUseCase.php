<?php

namespace App\UseCases\StudySession;

use App\Domain\StudySession\StudySessionRepositoryInterface;

class DeleteStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $sessionId): void
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            abort(404);
        }

        $this->repository->delete($session);
    }
}

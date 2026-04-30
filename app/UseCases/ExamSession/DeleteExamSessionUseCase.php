<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;

class DeleteExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): void
    {
        $session = $this->repository->findByIdAndUser($id, $userId);

        if ($session === null) {
            abort(404);
        }

        $this->repository->delete($session);
    }
}

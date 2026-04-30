<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Models\ExamSession;

class GetExamSessionUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): ?ExamSession
    {
        return $this->repository->findByIdAndUser($id, $userId);
    }
}

<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use Illuminate\Support\Collection;

class ListExamSessionsUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, ?string $status = null, ?string $subject = null): Collection
    {
        return $this->repository->findAllByUser($userId, $status, $subject);
    }
}

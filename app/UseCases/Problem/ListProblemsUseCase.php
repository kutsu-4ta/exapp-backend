<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use Illuminate\Support\Collection;

class ListProblemsUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId): Collection
    {
        return $this->repository->findAllByUser($userId);
    }
}

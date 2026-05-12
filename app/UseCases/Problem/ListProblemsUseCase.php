<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use Illuminate\Support\Collection;

class ListProblemsUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, ?int $limit = null, ?string $q = null, array $subjects = []): Collection
    {
        return $this->repository->findAllByUser($userId, $limit, $q, $subjects);
    }
}

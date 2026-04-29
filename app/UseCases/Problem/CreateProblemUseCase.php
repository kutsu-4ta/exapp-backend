<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;

class CreateProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, array $data): Problem
    {
        return $this->repository->create($userId, $data);
    }
}

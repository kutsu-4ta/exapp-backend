<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;

class UpdateProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $problemId, array $data): Problem
    {
        $problem = $this->repository->findByIdAndUser($problemId, $userId);

        if ($problem === null) {
            abort(404);
        }

        return $this->repository->update($problem, $data);
    }
}

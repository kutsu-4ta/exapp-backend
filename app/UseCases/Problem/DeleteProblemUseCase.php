<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;

class DeleteProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $problemId): void
    {
        $problem = $this->repository->findByIdAndUser($problemId, $userId);

        if ($problem === null) {
            abort(404);
        }

        $this->repository->delete($problem);
    }
}

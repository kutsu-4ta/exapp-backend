<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $id): Problem
    {
        $problem = $this->repository->findByIdAndUser($id, $userId);

        if ($problem === null) {
            throw new NotFoundHttpException('Problem not found.');
        }

        return $problem;
    }
}

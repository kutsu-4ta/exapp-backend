<?php

namespace App\UseCases\ProblemQuiz;

use App\Domain\ProblemQuiz\ProblemQuizRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;

class ListProblemQuizzesUseCase
{
    public function __construct(
        private readonly ProblemQuizRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $problemId): Collection
    {
        Problem::where('user_id', $userId)->where('id', $problemId)->firstOrFail();

        return $this->repository->listByProblem($problemId);
    }
}

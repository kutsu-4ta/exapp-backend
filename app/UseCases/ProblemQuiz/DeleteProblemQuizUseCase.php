<?php

namespace App\UseCases\ProblemQuiz;

use App\Domain\ProblemQuiz\ProblemQuizRepositoryInterface;
use App\Models\Problem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteProblemQuizUseCase
{
    public function __construct(
        private readonly ProblemQuizRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $problemId, int $quizId): void
    {
        Problem::where('user_id', $userId)->where('id', $problemId)->firstOrFail();

        $quiz = $this->repository->findByIdAndProblem($quizId, $problemId);

        if ($quiz === null) {
            throw new ModelNotFoundException();
        }

        $this->repository->delete($quiz);
    }
}

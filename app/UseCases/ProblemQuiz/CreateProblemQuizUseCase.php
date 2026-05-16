<?php

namespace App\UseCases\ProblemQuiz;

use App\Domain\ProblemQuiz\ProblemQuizRepositoryInterface;
use App\Models\Problem;
use App\Models\ProblemQuiz;

class CreateProblemQuizUseCase
{
    public function __construct(
        private readonly ProblemQuizRepositoryInterface $repository,
    ) {}

    public function __invoke(
        int $userId,
        int $problemId,
        string $quizType,
        string $question,
        array $options,
        ?int $correctIndex,
        string $explanation,
    ): ProblemQuiz {
        Problem::where('user_id', $userId)->where('id', $problemId)->firstOrFail();

        return $this->repository->create($problemId, $quizType, $question, $options, $correctIndex, $explanation);
    }
}

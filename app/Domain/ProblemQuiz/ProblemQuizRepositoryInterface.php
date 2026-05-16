<?php

namespace App\Domain\ProblemQuiz;

use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

interface ProblemQuizRepositoryInterface
{
    /** @return Collection<ProblemQuiz> */
    public function listByProblem(int $problemId): Collection;

    public function create(int $problemId, string $quizType, string $question, array $options, ?int $correctIndex, string $explanation): ProblemQuiz;

    public function findByIdAndProblem(int $quizId, int $problemId): ?ProblemQuiz;

    public function delete(ProblemQuiz $quiz): void;
}

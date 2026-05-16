<?php

namespace App\Infrastructure\Repositories;

use App\Domain\ProblemQuiz\ProblemQuizRepositoryInterface;
use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

class EloquentProblemQuizRepository implements ProblemQuizRepositoryInterface
{
    public function listByProblem(int $problemId): Collection
    {
        return ProblemQuiz::where('problem_id', $problemId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $problemId, string $quizType, string $question, array $options, ?int $correctIndex, string $explanation): ProblemQuiz
    {
        return ProblemQuiz::create([
            'problem_id'    => $problemId,
            'quiz_type'     => $quizType,
            'question'      => $question,
            'options'       => $options,
            'correct_index' => $correctIndex,
            'explanation'   => $explanation,
        ]);
    }

    public function findByIdAndProblem(int $quizId, int $problemId): ?ProblemQuiz
    {
        return ProblemQuiz::where('id', $quizId)->where('problem_id', $problemId)->first();
    }

    public function delete(ProblemQuiz $quiz): void
    {
        $quiz->delete();
    }
}

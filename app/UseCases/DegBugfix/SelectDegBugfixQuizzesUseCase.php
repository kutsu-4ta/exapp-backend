<?php

namespace App\UseCases\DegBugfix;

use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

class SelectDegBugfixQuizzesUseCase
{
    public function __invoke(int $userId, ?string $subject, int $limit): Collection
    {
        return ProblemQuiz::whereHas('problem', function ($q) use ($userId, $subject) {
            $q->where('user_id', $userId)
                ->when($subject, fn ($q) => $q->whereHas('subject', fn ($q) => $q->where('name', $subject)));
        })
            ->with(['problem.subject', 'problem.subCategory'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}

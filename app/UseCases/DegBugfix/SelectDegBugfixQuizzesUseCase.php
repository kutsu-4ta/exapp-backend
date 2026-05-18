<?php

namespace App\UseCases\DegBugfix;

use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

class SelectDegBugfixQuizzesUseCase
{
    public function __invoke(int $userId, ?string $subject, int $limit, array $ranks = []): Collection
    {
        return ProblemQuiz::whereHas('problem', function ($q) use ($userId, $subject, $ranks) {
            $q->where('user_id', $userId)
                ->when($subject, fn ($q) => $q->whereHas('subject', fn ($q) => $q->where('name', $subject)))
                ->when(
                    !empty($ranks),
                    fn ($q) => $q->whereHas('subCategory', fn ($q) => $q->whereIn('rank', $ranks))
                );
        })
            ->with(['problem.subject', 'problem.subCategory', 'problem.material'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}

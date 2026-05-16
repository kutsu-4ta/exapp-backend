<?php

namespace App\UseCases\DegBugfix;

use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

class SelectDegWordCardQuizzesUseCase
{
    public function __invoke(int $userId, ?string $subject, int $limit, bool $formulaOnly = false): Collection
    {
        return ProblemQuiz::where('quiz_type', 'word_card')
            ->whereHas('problem', function ($q) use ($userId, $subject, $formulaOnly) {
                $q->where('user_id', $userId)
                    ->when($subject, fn ($q) => $q->whereHas('subject', fn ($q) => $q->where('name', $subject)))
                    ->when($formulaOnly, fn ($q) => $q->where('is_formula', true));
            })
            ->with(['problem.subject', 'problem.subCategory', 'problem.material'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\UseCases\BugfixSession;

use App\Models\ProblemQuiz;
use Illuminate\Support\Collection;

class SelectSavedBugfixQuizzesUseCase
{
    public function __invoke(
        int $userId,
        string $quizType,
        array $subjects,
        array $failureTypes,
        array $subCategoryIds,
        array $proficiencies,
        ?string $touchedOrder,
        bool $formulaOnly,
        int $limit,
    ): Collection {
        $query = ProblemQuiz::where('quiz_type', $quizType)
            ->whereHas('problem', function ($q) use ($userId, $subjects, $failureTypes, $subCategoryIds, $proficiencies, $formulaOnly) {
                $q->where('user_id', $userId);

                if (!empty($subjects)) {
                    $q->whereHas('subject', fn ($q) => $q->whereIn('name', $subjects));
                }

                if (!empty($failureTypes)) {
                    $q->where(function ($q) use ($failureTypes) {
                        foreach ($failureTypes as $type) {
                            $q->orWhereJsonContains('failure_types', $type);
                        }
                    });
                }

                if (!empty($subCategoryIds)) {
                    $q->whereIn('sub_category_id', $subCategoryIds);
                }

                if (!empty($proficiencies)) {
                    $q->whereIn('proficiency', $proficiencies);
                }

                if ($formulaOnly) {
                    $q->where('is_formula', true);
                }
            })
            ->with(['problem.subject', 'problem.subCategory', 'problem.material']);

        match ($touchedOrder) {
            'recent' => $query->orderByRaw('(SELECT last_touched_at FROM problems WHERE problems.id = problem_quizzes.problem_id) DESC NULLS LAST'),
            'old'    => $query->orderByRaw('(SELECT last_touched_at FROM problems WHERE problems.id = problem_quizzes.problem_id) ASC NULLS FIRST'),
            default  => $query->inRandomOrder(),
        };

        return $query->limit($limit)->get();
    }
}

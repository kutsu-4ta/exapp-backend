<?php

namespace App\UseCases\FlashCard;

use App\Enums\Proficiency;
use App\Models\Problem;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SelectFlashCardProblemsUseCase
{
    public function __invoke(int $userId, FlashCardFilter $filter): Collection
    {
        $subject = Subject::where('user_id', $userId)->where('name', $filter->subject)->first();

        if ($subject === null) {
            return collect();
        }

        $limit    = $filter->limit;
        $selected = collect();

        // Tier 1: failureType + subCategoryIds（フル条件）
        $tier1 = $this->buildQuery($userId, $subject->id, $filter, useFailureType: true, useSubCategory: true)
            ->limit($limit)
            ->get();
        $selected = $selected->concat($tier1);

        if ($selected->count() >= $limit) {
            return $selected->take($limit);
        }

        // Tier 2: failureType のみ（subCategoryIds を緩める）
        if (!empty($filter->subCategoryIds)) {
            $tier2 = $this->buildQuery($userId, $subject->id, $filter, useFailureType: true, useSubCategory: false)
                ->whereNotIn('id', $selected->pluck('id'))
                ->limit($limit - $selected->count())
                ->get();
            $selected = $selected->concat($tier2);

            if ($selected->count() >= $limit) {
                return $selected->take($limit);
            }
        }

        // Tier 3: subCategoryIds のみ（failureType を緩める）
        if (!empty($filter->failureTypes) && !empty($filter->subCategoryIds)) {
            $tier3 = $this->buildQuery($userId, $subject->id, $filter, useFailureType: false, useSubCategory: true)
                ->whereNotIn('id', $selected->pluck('id'))
                ->limit($limit - $selected->count())
                ->get();
            $selected = $selected->concat($tier3);

            if ($selected->count() >= $limit) {
                return $selected->take($limit);
            }
        }

        // Tier 4: 全問題（習熟度低い順）
        $incorrect = Proficiency::Incorrect->value;
        $partial   = Proficiency::Partial->value;

        $tier4 = Problem::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->with(['subject', 'subCategory'])
            ->when($filter->formulaOnly, fn ($q) => $q->where('is_formula', true))
            ->whereNotIn('id', $selected->pluck('id'))
            ->orderByRaw('CASE proficiency WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [$incorrect, $partial])
            ->orderByRaw('RANDOM()')
            ->limit($limit - $selected->count())
            ->get();

        return $selected->concat($tier4)->take($limit);
    }

    private function buildQuery(int $userId, int $subjectId, FlashCardFilter $filter, bool $useFailureType, bool $useSubCategory): Builder
    {
        $query = Problem::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->with(['subject', 'subCategory'])
            ->when($filter->formulaOnly, fn ($q) => $q->where('is_formula', true));

        if ($useFailureType && !empty($filter->failureTypes)) {
            $query->where(function ($q) use ($filter) {
                foreach ($filter->failureTypes as $type) {
                    $q->orWhereJsonContains('failure_types', $type);
                }
            });
        }

        if ($useSubCategory && !empty($filter->subCategoryIds)) {
            $query->whereIn('sub_category_id', $filter->subCategoryIds);
        }

        if (!empty($filter->proficiencies)) {
            $query->whereIn('proficiency', $filter->proficiencies);
        }

        $this->applyTouchedOrder($query, $filter->touchedOrder);

        return $query;
    }

    private function applyTouchedOrder(Builder $query, ?string $order): void
    {
        match ($order) {
            'recent' => $query->orderByRaw('last_touched_at DESC NULLS LAST'),
            'old'    => $query->orderByRaw('last_touched_at ASC NULLS FIRST'),
            default  => $query->inRandomOrder(),
        };
    }
}

<?php

namespace App\UseCases\MorningBugfix;

use App\Enums\Proficiency;
use App\Models\Problem;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SelectMorningProblemsUseCase
{
    public function __invoke(int $userId, BugfixFilter $filter): Collection
    {
        // subject名をIDに変換（各Tierクエリでの相関サブクエリを回避）
        $subjectId = null;
        if ($filter->subject !== null) {
            $subjectId = Subject::where('user_id', $userId)->where('name', $filter->subject)->value('id');
            if ($subjectId === null) {
                return collect();
            }
        }

        return $filter->morningMode
            ? $this->selectMorningMode($userId, $filter, $subjectId)
            : $this->selectFlexMode($userId, $filter, $subjectId);
    }

    // ── Morning Bugfix: 3段階フォールバック（昨日→7日→全期間）─────────────────

    private function selectMorningMode(int $userId, BugfixFilter $filter, ?int $subjectId): Collection
    {
        $date         = $filter->date ?? Carbon::today();
        $yesterday    = $date->copy()->subDay()->toDateString();
        $sevenDaysAgo = $date->copy()->subDays(7)->toDateString();
        $limit        = $filter->limit;

        $selected = collect();

        // Tier 1: 昨日に触れた
        $tier1 = $this->morningBase($userId, $filter, $subjectId)
            ->whereDate('last_touched_at', $yesterday)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
        $selected = $selected->concat($tier1);

        if ($selected->count() >= $limit) {
            return $selected->take($limit);
        }

        // Tier 2: 直近7日以内（昨日を除く）
        $tier2 = $this->morningBase($userId, $filter, $subjectId)
            ->whereDate('last_touched_at', '>=', $sevenDaysAgo)
            ->whereDate('last_touched_at', '<', $yesterday)
            ->whereNotIn('id', $selected->pluck('id'))
            ->inRandomOrder()
            ->limit($limit - $selected->count())
            ->get();
        $selected = $selected->concat($tier2);

        if ($selected->count() >= $limit) {
            return $selected->take($limit);
        }

        // Tier 3: 全期間（習熟度の低い順 ×>△>○）
        $incorrect = Proficiency::Incorrect->value;
        $partial   = Proficiency::Partial->value;

        $tier3 = $this->morningBase($userId, $filter, $subjectId)
            ->whereNotIn('id', $selected->pluck('id'))
            ->orderByRaw('CASE proficiency WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [$incorrect, $partial])
            ->orderByRaw('RANDOM()')
            ->limit($limit - $selected->count())
            ->get();

        return $selected->concat($tier3)->take($limit);
    }

    private function morningBase(int $userId, BugfixFilter $filter, ?int $subjectId): Builder
    {
        $query = Problem::where('problems.user_id', $userId)
            ->with(['subject', 'subCategory', 'material'])
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($filter->formulaOnly, fn ($q) => $q->where('is_formula', true));

        if ($filter->failureTypes !== []) {
            $query->whereJsonContains('failure_types', $filter->failureTypes);
        }

        if (!empty($filter->ranks)) {
            $query->whereHas('subCategory', fn ($q) => $q->whereIn('rank', $filter->ranks));
        }

        return $query;
    }

    // ── Flash Bugfix: 汎用フォールバック（条件を段階的に緩める）─────────────────

    private function selectFlexMode(int $userId, BugfixFilter $filter, ?int $subjectId): Collection
    {
        $limit    = $filter->limit;
        $selected = collect();

        // Tier 1: failureType + subCategoryId（フル条件）
        $tier1 = $this->flexQuery($userId, $filter, $subjectId, useFailureType: true, useSubCategory: true)
            ->whereNotIn('id', $selected->pluck('id'))
            ->limit($limit - $selected->count())
            ->get();
        $selected = $selected->concat($tier1);

        if ($selected->count() >= $limit) {
            return $selected->take($limit);
        }

        // Tier 2: failureType のみ（subCategoryId を緩める）
        if ($filter->subCategoryIds !== []) {
            $tier2 = $this->flexQuery($userId, $filter, $subjectId, useFailureType: true, useSubCategory: false)
                ->whereNotIn('id', $selected->pluck('id'))
                ->limit($limit - $selected->count())
                ->get();
            $selected = $selected->concat($tier2);

            if ($selected->count() >= $limit) {
                return $selected->take($limit);
            }
        }

        // Tier 3: subCategoryId のみ（failureType を緩める）
        if ($filter->failureTypes !== null && $filter->subCategoryId !== null) {
            $tier3 = $this->flexQuery($userId, $filter, $subjectId, useFailureType: false, useSubCategory: true)
                ->whereNotIn('id', $selected->pluck('id'))
                ->limit($limit - $selected->count())
                ->get();
            $selected = $selected->concat($tier3);

            if ($selected->count() >= $limit) {
                return $selected->take($limit);
            }
        }

        // Tier 4: 全問題（proficiency フィルターも解除、習熟度低い順）
        $incorrect = Proficiency::Incorrect->value;
        $partial   = Proficiency::Partial->value;

        $tier4 = Problem::where('user_id', $userId)
            ->with(['subject', 'subCategory', 'material'])
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($filter->formulaOnly, fn ($q) => $q->where('is_formula', true))
            ->whereNotIn('id', $selected->pluck('id'))
            ->orderByRaw('CASE proficiency WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [$incorrect, $partial])
            ->orderByRaw('RANDOM()')
            ->limit($limit - $selected->count())
            ->get();

        return $selected->concat($tier4)->take($limit);
    }

    private function flexQuery(int $userId, BugfixFilter $filter, ?int $subjectId, bool $useFailureType, bool $useSubCategory): Builder
    {
        $query = Problem::where('user_id', $userId)
            ->with(['subject', 'subCategory', 'material'])
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($filter->formulaOnly, fn ($q) => $q->where('is_formula', true));

        // ミス種別の複数選択対応 (JSON配列内のいずれか)
        if ($useFailureType && !empty($filter->failureTypes)) {
            $query->where(function ($q) use ($filter) {
                foreach ($filter->failureTypes as $type) {
                    $q->orWhereJsonContains('failure_types', $type);
                }
            });
        }

        // 論点の複数選択対応
        if ($useSubCategory && !empty($filter->subCategoryIds)) {
            $query->whereIn('sub_category_id', $filter->subCategoryIds);
        }

        if (!empty($filter->proficiencies)) {
            $query->whereIn('proficiency', $filter->proficiencies);
        }

        if (!empty($filter->ranks)) {
            $query->whereHas('subCategory', fn ($q) => $q->whereIn('rank', $filter->ranks));
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

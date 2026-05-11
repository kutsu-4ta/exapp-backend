<?php

namespace App\UseCases\MorningBugfix;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\Problem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SelectMorningProblemsUseCase
{
    private const LIMIT = 5;

    public function __invoke(int $userId, ?Carbon $date = null): Collection
    {
        $date      = $date ?? Carbon::today();
        $yesterday = $date->copy()->subDay()->toDateString();
        $sevenDaysAgo = $date->copy()->subDays(7)->toDateString();

        $selected = collect();

        // Tier 1: 昨日に触れた定義ミス
        $tier1 = $this->baseQuery($userId)
            ->whereDate('last_touched_at', $yesterday)
            ->inRandomOrder()
            ->limit(self::LIMIT)
            ->get();

        $selected = $selected->concat($tier1);

        if ($selected->count() >= self::LIMIT) {
            return $selected->take(self::LIMIT);
        }

        // Tier 2: 直近7日以内の定義ミス（昨日を除く、取得済みを除外）
        $tier2 = $this->baseQuery($userId)
            ->whereDate('last_touched_at', '>=', $sevenDaysAgo)
            ->whereDate('last_touched_at', '<', $yesterday)
            ->whereNotIn('id', $selected->pluck('id'))
            ->inRandomOrder()
            ->limit(self::LIMIT - $selected->count())
            ->get();

        $selected = $selected->concat($tier2);

        if ($selected->count() >= self::LIMIT) {
            return $selected->take(self::LIMIT);
        }

        // Tier 3: 全期間の定義ミス（習熟度の低い順＝×>△>○、取得済みを除外）
        $incorrect = Proficiency::Incorrect->value;
        $partial   = Proficiency::Partial->value;

        $tier3 = $this->baseQuery($userId)
            ->whereNotIn('id', $selected->pluck('id'))
            ->orderByRaw("CASE proficiency WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END", [$incorrect, $partial])
            ->orderByRaw('RANDOM()')
            ->limit(self::LIMIT - $selected->count())
            ->get();

        return $selected->concat($tier3)->take(self::LIMIT);
    }

    private function baseQuery(int $userId): Builder
    {
        return Problem::where('user_id', $userId)
            ->whereJsonContains('failure_types', FailureType::MissingDefinition->value)
            ->with(['subject', 'subCategory']);
    }
}

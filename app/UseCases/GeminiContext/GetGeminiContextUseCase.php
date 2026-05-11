<?php

namespace App\UseCases\GeminiContext;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\Problem;
use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;
use App\Models\SubjectSetting;
use App\Models\StudySession;
use App\UseCases\Dashboard\GetDashboardStatsUseCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetGeminiContextUseCase
{
    public function __construct(
        private readonly GetDashboardStatsUseCase $dashboardUseCase,
    ) {}

    public function __invoke(int $userId, ?int $year = null, ?int $month = null): array
    {
        $now   = Carbon::now();
        $year  = $year  ?? $now->year;
        $month = $month ?? $now->month;

        $dashboard = ($this->dashboardUseCase)($userId, $year, $month);

        $subjects = Subject::orderBy('display_order')->get();

        $settings = SubjectSetting::where('user_id', $userId)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy('subject_id');

        $monthlyGoals = SubjectMonthlyGoal::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy('subject_id');

        $studyMinutesBySubject = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->groupBy('study_sessions.subject_id')
            ->get([
                'study_sessions.subject_id',
                DB::raw('SUM(study_sessions.minutes) as minutes'),
            ])
            ->keyBy('subject_id');

        $weakProblemsBySubject = Problem::where('user_id', $userId)
            ->whereIn('proficiency', [Proficiency::Partial->value, Proficiency::Incorrect->value])
            ->get(['subject_id', 'failure_types'])
            ->groupBy('subject_id');

        $subjectData = $subjects->map(function (Subject $subject) use (
            $settings,
            $monthlyGoals,
            $studyMinutesBySubject,
            $weakProblemsBySubject,
        ) {
            $id = $subject->id;

            $finalTarget = $settings->get($id)?->final_target;
            $monthlyGoal = $monthlyGoals->get($id)?->goal;
            $studyMinutes = (int) ($studyMinutesBySubject->get($id)?->minutes ?? 0);

            $problems  = $weakProblemsBySubject->get($id, collect());
            $problemCount = $problems->count();

            $failureStats = $this->buildFailureStats($problems);

            return [
                'subject'      => $subject->name,
                'finalTarget'  => $finalTarget,
                'monthlyGoal'  => $monthlyGoal,
                'studyMinutes' => $studyMinutes,
                'problemCount' => $problemCount,
                'failureStats' => $failureStats,
            ];
        })->values()->toArray();

        return [
            'year'      => $year,
            'month'     => $month,
            'dashboard' => $dashboard,
            'subjects'  => $subjectData,
        ];
    }

    private function buildFailureStats(Collection $problems): array
    {
        $totals = array_fill_keys(
            array_map(fn (FailureType $t) => $t->value, FailureType::cases()),
            0,
        );

        foreach ($problems as $problem) {
            foreach ($problem->failure_types ?? [] as $type) {
                if (isset($totals[$type])) {
                    $totals[$type]++;
                }
            }
        }

        $totalCount = array_sum($totals);

        return array_values(array_map(function (FailureType $type) use ($totals, $totalCount) {
            $count = $totals[$type->value];
            return [
                'type'  => $type->value,
                'count' => $count,
                'ratio' => $totalCount > 0 ? round($count / $totalCount, 4) : 0.0,
            ];
        }, FailureType::cases()));
    }
}

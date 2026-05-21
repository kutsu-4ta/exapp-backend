<?php

namespace App\UseCases\Subject;

use App\Enums\ExamSessionStatus;
use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\Problem;
use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;
use App\Models\SubjectSetting;
use App\Models\StudySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetSubjectsSummaryUseCase
{
    public function __invoke(int $userId, ?int $year = null, ?int $month = null): array
    {
        $now   = Carbon::now();
        $year  = $year  ?? $now->year;
        $month = $month ?? $now->month;

        $subjects   = Subject::orderBy('display_order')->get();
        $subjectIds = $subjects->pluck('id');

        $settings = SubjectSetting::where('user_id', $userId)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->keyBy('subject_id');

        $monthlyGoals = SubjectMonthlyGoal::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('subject_id', $subjectIds)
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

        $latestExamBySubject = $this->fetchLatestExamScores($userId, $subjectIds);

        $subjectData = $subjects->map(function (Subject $subject) use (
            $settings,
            $monthlyGoals,
            $studyMinutesBySubject,
            $weakProblemsBySubject,
            $latestExamBySubject,
        ) {
            $id       = $subject->id;
            $problems = $weakProblemsBySubject->get($id, collect());
            $examRow  = $latestExamBySubject->get($id);

            return [
                'subject'         => $subject->name,
                'finalTarget'     => $settings->get($id)?->final_target,
                'monthlyGoal'     => $monthlyGoals->get($id)?->goal,
                'studyMinutes'    => (int) ($studyMinutesBySubject->get($id)?->minutes ?? 0),
                'problemCount'    => $problems->count(),
                'failureStats'    => $this->buildFailureStats($problems),
                'recentExamScore' => $examRow ? [
                    'examYear'    => $examRow->exam_year,
                    'score'       => (int) $examRow->score,
                    'completedAt' => $examRow->completed_at
                        ? Carbon::parse($examRow->completed_at)->toDateString()
                        : null,
                    'rankStats'   => $examRow->rank_stats,
                ] : null,
            ];
        })->values()->toArray();

        return [
            'year'     => $year,
            'month'    => $month,
            'subjects' => $subjectData,
        ];
    }

    private function fetchLatestExamScores(int $userId, Collection $subjectIds): Collection
    {
        // 科目ごとの最新完了セッションを取得（completed_at DESC → exam_year DESC の先頭 = 最新）
        $latestSessions = DB::table('exam_sessions as es')
            ->where('es.user_id', $userId)
            ->where('es.status', ExamSessionStatus::Completed->value)
            ->whereIn('es.subject_id', $subjectIds)
            ->orderByDesc('es.completed_at')
            ->orderByDesc('es.exam_year')
            ->get(['es.id', 'es.subject_id', 'es.exam_year', 'es.completed_at'])
            ->unique('subject_id')
            ->keyBy('subject_id');

        if ($latestSessions->isEmpty()) {
            return collect();
        }

        $sessionIds = $latestSessions->pluck('id');

        $scores = DB::table('exam_questions as eq')
            ->whereIn('eq.exam_session_id', $sessionIds)
            ->groupBy('eq.exam_session_id')
            ->get([
                'eq.exam_session_id',
                DB::raw("SUM(CASE WHEN eq.is_correct = true THEN eq.point ELSE 0 END) as score"),
            ])
            ->keyBy('exam_session_id');

        $rankStatsRows = DB::table('exam_questions as eq')
            ->whereIn('eq.exam_session_id', $sessionIds)
            ->whereNotNull('eq.rank')
            ->groupBy('eq.exam_session_id', 'eq.rank')
            ->orderBy('eq.rank')
            ->get([
                'eq.exam_session_id',
                'eq.rank',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN eq.is_correct = true THEN 1 ELSE 0 END) as correct'),
            ])
            ->groupBy('exam_session_id');

        return $latestSessions->map(function ($session) use ($scores, $rankStatsRows) {
            $sid      = $session->id;
            $score    = $scores->get($sid);
            $rankRows = $rankStatsRows->get($sid, collect());

            $rankStats = $rankRows->map(fn ($r) => [
                'rank'        => $r->rank,
                'correctRate' => $r->total > 0 ? round($r->correct / $r->total, 4) : 0.0,
                'count'       => (int) $r->total,
            ])->values()->toArray();

            return (object) [
                'exam_year'    => $session->exam_year,
                'score'        => (int) ($score?->score ?? 0),
                'completed_at' => $session->completed_at,
                'rank_stats'   => $rankStats,
            ];
        });
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

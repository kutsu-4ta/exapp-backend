<?php

namespace App\UseCases\GeminiContext;

use App\Enums\ExamSessionStatus;
use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\Problem;
use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;
use App\Models\SubjectSetting;
use App\Models\StudySession;
use App\Models\UserProfile;
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
        $subjects  = Subject::orderBy('display_order')->get();
        $subjectIds = $subjects->pluck('id');

        // ── SubjectSettings / MonthlyGoals / StudyMinutes / WeakProblems ────────

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

        // ── UserProfile ──────────────────────────────────────────────────────────

        $userProfile = UserProfile::where('user_id', $userId)
            ->first(['occupation', 'goal', 'weak_areas', 'strong_areas']);

        // ── 直近7日間 DailyLogs ──────────────────────────────────────────────────

        $today        = Carbon::today();
        $sevenDaysAgo = $today->copy()->subDays(6);

        $dailyLogRows = DB::table('daily_logs as dl')
            ->leftJoin('study_sessions as ss', 'ss.daily_log_id', '=', 'dl.id')
            ->where('dl.user_id', $userId)
            ->whereBetween('dl.date', [$sevenDaysAgo->toDateString(), $today->toDateString()])
            ->groupBy('dl.id', 'dl.date', 'dl.reflection')
            ->orderBy('dl.date')
            ->get([
                'dl.date',
                'dl.reflection',
                DB::raw('COALESCE(SUM(ss.minutes), 0) as study_minutes'),
            ])
            ->keyBy('date');

        // 7日分ゼロ補填（記録がない日も含める）
        $recentDailyLogs = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $row  = $dailyLogRows->get($date);
            $recentDailyLogs[] = [
                'date'         => $date,
                'studyMinutes' => $row ? (int) $row->study_minutes : 0,
                'reflection'   => $row?->reflection,
            ];
        }

        // ── 科目別 直近完了過去問スコア ──────────────────────────────────────────

        $latestExamBySubject = $this->fetchLatestExamScores($userId, $subjectIds);

        // ── 科目データ組み立て ───────────────────────────────────────────────────

        $subjectData = $subjects->map(function (Subject $subject) use (
            $settings,
            $monthlyGoals,
            $studyMinutesBySubject,
            $weakProblemsBySubject,
            $latestExamBySubject,
        ) {
            $id = $subject->id;

            $problems     = $weakProblemsBySubject->get($id, collect());
            $examRow      = $latestExamBySubject->get($id);

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
                ] : null,
            ];
        })->values()->toArray();

        return [
            'year'            => $year,
            'month'           => $month,
            'profile'         => [
                'occupation' => $userProfile?->occupation,
                'goal'       => $userProfile?->goal,
                'weakAreas'  => $userProfile?->weak_areas,
                'strongAreas' => $userProfile?->strong_areas,
            ],
            'dashboard'       => $dashboard,
            'recentDailyLogs' => $recentDailyLogs,
            'subjects'        => $subjectData,
        ];
    }

    /**
     * 科目別の直近完了済み過去問スコアを1クエリで取得。
     * exam_questions.point の合計（is_correct=true のみ）を score として返す。
     */
    private function fetchLatestExamScores(int $userId, Collection $subjectIds): Collection
    {
        $rows = DB::table('exam_sessions as es')
            ->join('exam_questions as eq', 'eq.exam_session_id', '=', 'es.id')
            ->where('es.user_id', $userId)
            ->where('es.status', ExamSessionStatus::Completed->value)
            ->whereIn('es.subject_id', $subjectIds)
            ->groupBy('es.id', 'es.subject_id', 'es.exam_year', 'es.completed_at')
            ->orderByDesc('es.completed_at')
            ->orderByDesc('es.exam_year')
            ->get([
                'es.subject_id',
                'es.exam_year',
                'es.completed_at',
                DB::raw("SUM(CASE WHEN eq.is_correct = true THEN eq.point ELSE 0 END) as score"),
            ]);

        // 科目ごとに最新1件のみ（クエリが completed_at DESC 順なので unique で先頭 = 最新）
        return $rows->unique('subject_id')->keyBy('subject_id');
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

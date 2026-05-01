<?php

namespace App\UseCases\AiAdvice;

use App\Enums\AiAdviceMode;
use App\Models\Problem;
use App\Models\StudySession;
use App\Models\UserProfile as UserProfileModel;
use App\Services\AiAdvice\AdviceContext;
use App\Services\AiAdvice\PromptBuilder;
use App\Services\AiAdvice\UserProfile;
use App\Services\GeminiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetAiAdviceUseCase
{
    public function __construct(
        private readonly GeminiService $gemini,
        private readonly PromptBuilder $promptBuilder,
    ) {}

    public function __invoke(int $userId, AiAdviceMode $mode): string
    {
        $now      = Carbon::now();
        $dbProfile = UserProfileModel::where('user_id', $userId)->first();
        $profile  = $this->toServiceProfile($dbProfile);
        $context  = $this->buildContext($userId, $now, $profile);

        $systemInstruction = $this->promptBuilder->systemInstruction($mode, $context);
        $userPrompt        = $this->promptBuilder->userPrompt($mode, $context);

        // ユーザーが個別トークンを登録していればそちらを優先
        $geminiToken = $dbProfile?->gemini_token;

        return $this->gemini->generateAdvice($systemInstruction, $userPrompt, $geminiToken);
    }

    private function toServiceProfile(?UserProfileModel $db): UserProfile
    {
        if ($db === null) {
            return UserProfile::default();
        }

        return new UserProfile(
            occupation:          $db->occupation          ?? UserProfile::default()->occupation,
            goal:                $db->goal                ?? UserProfile::default()->goal,
            weakAreas:           $db->weak_areas          ?? UserProfile::default()->weakAreas,
            strongAreas:         $db->strong_areas        ?? UserProfile::default()->strongAreas,
            interests:           $db->interests           ?? UserProfile::default()->interests,
            weeklyTargetMinutes: UserProfile::default()->weeklyTargetMinutes,
        );
    }

    // ----------------------------------------------------------------

    private function buildContext(int $userId, Carbon $now, UserProfile $profile): AdviceContext
    {
        $year         = $now->year;
        $month        = $now->month;
        $today        = $now->toDateString();
        $sevenDaysAgo = $now->copy()->subDays(6)->toDateString();
        $weekStart    = $now->copy()->startOfWeek()->toDateString();

        $subjectMinutes = $this->subjectMinutes($userId, $year, $month);
        $last7DaysMin   = $this->rangeMinutes($userId, $sevenDaysAgo, $today);
        $thisWeekMin    = $this->rangeMinutes($userId, $weekStart, $today);
        $studyDays      = $this->studyDays($userId, $year, $month);
        $totalMonthMin  = array_sum($subjectMinutes);
        $weakSubjects   = $this->weakSubjects($userId);
        $currentStreak  = $this->currentStreak($userId, $now);
        $lastSubject    = $this->lastStudiedSubject($userId);

        return new AdviceContext(
            year:               $year,
            month:              $month,
            totalMonthMinutes:  $totalMonthMin,
            studyDays:          $studyDays,
            last7DaysMinutes:   $last7DaysMin,
            thisWeekMinutes:    $thisWeekMin,
            currentStreak:      $currentStreak,
            subjectMinutes:     $subjectMinutes,
            weakSubjects:       $weakSubjects,
            lastSubject:        $lastSubject,
            profile:            $profile,
        );
    }

    // ----------------------------------------------------------------
    // DB クエリ群
    // ----------------------------------------------------------------

    /** 今月の科目別学習分数（降順）。['科目名' => 分数] の連想配列を返す */
    private function subjectMinutes(int $userId, int $year, int $month): array
    {
        return StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc(DB::raw('SUM(study_sessions.minutes)'))
            ->get([
                'subjects.name as subject_name',
                DB::raw('SUM(study_sessions.minutes) as total_minutes'),
            ])
            ->mapWithKeys(fn ($row) => [$row->subject_name => (int) $row->total_minutes])
            ->toArray();
    }

    /** 指定期間の合計学習分数 */
    private function rangeMinutes(int $userId, string $from, string $to): int
    {
        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereBetween('daily_logs.date', [$from, $to])
            ->sum('study_sessions.minutes');
    }

    /** 今月の学習日数 */
    private function studyDays(int $userId, int $year, int $month): int
    {
        return (int) DB::table('daily_logs')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('study_sessions')
                ->whereColumn('study_sessions.daily_log_id', 'daily_logs.id'))
            ->count();
    }

    /** 苦手問題が多い科目（上位3件）。['科目名' => 件数] の連想配列を返す */
    private function weakSubjects(int $userId): array
    {
        return Problem::join('subjects', 'subjects.id', '=', 'problems.subject_id')
            ->where('problems.user_id', $userId)
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc(DB::raw('COUNT(problems.id)'))
            ->limit(3)
            ->get([
                'subjects.name as subject_name',
                DB::raw('COUNT(problems.id) as problem_count'),
            ])
            ->mapWithKeys(fn ($row) => [$row->subject_name => (int) $row->problem_count])
            ->toArray();
    }

    /** 今日まで遡る連続学習日数 */
    private function currentStreak(int $userId, Carbon $now): int
    {
        $streak  = 0;
        $current = $now->copy()->startOfDay();

        while (true) {
            $dateStr = $current->toDateString();
            $hasSession = DB::table('daily_logs')
                ->where('user_id', $userId)
                ->where('date', $dateStr)
                ->whereExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('study_sessions')
                    ->whereColumn('study_sessions.daily_log_id', 'daily_logs.id'))
                ->exists();

            if (!$hasSession) {
                break;
            }

            $streak++;
            $current->subDay();

            // 無限ループ防止（最大365日）
            if ($streak >= 365) {
                break;
            }
        }

        return $streak;
    }

    /** 最後に学習した科目名（なければ空文字） */
    private function lastStudiedSubject(int $userId): string
    {
        $result = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->orderByDesc('daily_logs.date')
            ->orderByDesc('study_sessions.id')
            ->value('subjects.name');

        return $result ?? '（未記録）';
    }
}

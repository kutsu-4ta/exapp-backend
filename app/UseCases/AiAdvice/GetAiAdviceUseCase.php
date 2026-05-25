<?php

namespace App\UseCases\AiAdvice;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Enums\AiAdviceMode;
use App\Models\AiUserProfile;
use App\Models\Problem;
use App\Models\StudySession;
use App\Models\UserProfile as UserProfileModel;
use App\Services\AiAdvice\AdviceContext;
use App\Services\AiAdvice\AdviceMessageFormatter;
use App\Services\AiAdvice\PromptBuilder;
use App\Services\AiAdvice\UserProfile;
use App\Services\AiProfile\AiProfileBuilder;
use App\Services\GeminiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetAiAdviceUseCase
{
    public function __construct(
        private readonly GeminiService               $gemini,
        private readonly PromptBuilder               $promptBuilder,
        private readonly AdviceMessageFormatter      $formatter,
        private readonly AiProfileBuilder            $profileBuilder,
        private readonly MaterialRepositoryInterface $materialRepository,
    ) {}

    public function __invoke(int $userId, AiAdviceMode $mode): string
    {
        $now       = Carbon::now();
        $dbProfile = UserProfileModel::where('user_id', $userId)->first();
        $aiProfile = AiUserProfile::where('user_id', $userId)->first();

        $englishProfile = $this->resolveEnglishProfile($userId, $dbProfile, $aiProfile);

        $materials = $this->materialRepository
            ->findAll($userId)
            ->pluck('name')
            ->all();

        $context = $this->buildContext($userId, $now, $englishProfile, $materials);

        $systemInstruction = $this->promptBuilder->systemInstruction($mode, $context);
        $userPrompt        = $this->promptBuilder->userPrompt($mode, $context);

        $geminiToken = $dbProfile?->gemini_token;
        $geminiModel = $aiProfile?->gemini_model;

        $text = $this->gemini->generateText($systemInstruction, $userPrompt, $geminiToken, $geminiModel);

        return $this->formatter->format($mode, $text);
    }

    // ----------------------------------------------------------------

    /**
     * ai_user_profiles から英語化済みプロファイルを取得する。
     * レコードがなければ AiProfileBuilder でその場生成してキャッシュする。
     * PromptBuilder は UserProfile DTO を受け取るので、normalized_prompt_json から再マッピングする。
     */
    private function resolveEnglishProfile(int $userId, ?UserProfileModel $dbProfile, ?AiUserProfile $aiProfile = null): UserProfile
    {
        try {
            $aiProfile ??= AiUserProfile::where('user_id', $userId)->first();

            if ($aiProfile === null && $dbProfile !== null) {
                $aiProfile = $this->profileBuilder->buildAndSave($dbProfile);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AiUserProfile resolve failed, using default profile', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            return UserProfile::default();
        }

        $normalized    = $aiProfile?->normalized_prompt_json ?? [];
        $weeklyTargetH = $normalized['weeklyTargetH'] ?? 45;

        return new UserProfile(
            occupation:          $normalized['occupation'] ?? UserProfile::default()->occupation,
            goal:                $normalized['goal']       ?? UserProfile::default()->goal,
            weakAreas:           implode(', ', $normalized['weak']      ?? []),
            strongAreas:         implode(', ', $normalized['strong']    ?? []),
            interests:           implode(', ', $normalized['interests'] ?? []),
            weeklyTargetMinutes: (int) ($weeklyTargetH * 60),
        );
    }

    private function toDefaultProfile(): UserProfile
    {
        return UserProfile::default();
    }

    // ----------------------------------------------------------------

    private function buildContext(int $userId, Carbon $now, UserProfile $profile, array $materials): AdviceContext
    {
        $effectiveNow = $now->hour < 4 ? $now->copy()->subDay() : $now->copy();

        $year         = $effectiveNow->year;
        $month        = $effectiveNow->month;
        $today        = $effectiveNow->toDateString();
        $sevenDaysAgo = $effectiveNow->copy()->subDays(6)->toDateString();
        $weekStart    = $effectiveNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

        $subjectMinutes        = $this->subjectMinutes($userId, $year, $month);
        $allTimeSubjectMinutes = $this->allTimeSubjectMinutes($userId);
        $last7DaysMin          = $this->rangeMinutes($userId, $sevenDaysAgo, $today);
        $thisWeekMin           = $this->rangeMinutes($userId, $weekStart, $today);
        $studyDays             = $this->studyDays($userId, $year, $month);
        $totalMonthMin         = array_sum($subjectMinutes);
        $weakSubjects          = $this->weakSubjects($userId);
        $untouchedSubjects     = $this->untouchedSubjects($userId, $effectiveNow);
        $failureTypesBySubject = $this->failureTypesBySubject($userId);
        $currentStreak         = $this->currentStreak($userId, $effectiveNow);
        $lastSubject           = $this->lastStudiedSubject($userId);

        return new AdviceContext(
            year:                  $year,
            month:                 $month,
            totalMonthMinutes:     $totalMonthMin,
            studyDays:             $studyDays,
            last7DaysMinutes:      $last7DaysMin,
            thisWeekMinutes:       $thisWeekMin,
            currentStreak:         $currentStreak,
            subjectMinutes:        $subjectMinutes,
            allTimeSubjectMinutes: $allTimeSubjectMinutes,
            weakSubjects:          $weakSubjects,
            untouchedSubjects:     $untouchedSubjects,
            failureTypesBySubject: $failureTypesBySubject,
            lastSubject:           $lastSubject,
            profile:               $profile,
            materials:             $materials,
        );
    }

    // ----------------------------------------------------------------
    // DB クエリ群
    // ----------------------------------------------------------------

    private function allTimeSubjectMinutes(int $userId): array
    {
        return StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc(DB::raw('SUM(study_sessions.minutes)'))
            ->get([
                'subjects.name as subject_name',
                DB::raw('SUM(study_sessions.minutes) as total_minutes'),
            ])
            ->mapWithKeys(fn ($row) => [$row->subject_name => (int) $row->total_minutes])
            ->toArray();
    }

    private function untouchedSubjects(int $userId, Carbon $now, int $days = 30): array
    {
        $since = $now->copy()->subDays($days)->toDateString();

        $allSubjects = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->distinct()
            ->pluck('subjects.name')
            ->toArray();

        $recentSubjects = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->where('daily_logs.date', '>=', $since)
            ->distinct()
            ->pluck('subjects.name')
            ->toArray();

        return array_values(array_diff($allSubjects, $recentSubjects));
    }

    private function failureTypesBySubject(int $userId): array
    {
        $problems = Problem::join('subjects', 'subjects.id', '=', 'problems.subject_id')
            ->where('problems.user_id', $userId)
            ->whereNotNull('problems.failure_types')
            ->get(['subjects.name as subject_name', 'problems.failure_types']);

        $result = [];
        foreach ($problems as $p) {
            $subject = $p->subject_name;
            $types   = is_array($p->failure_types) ? $p->failure_types : (json_decode($p->failure_types, true) ?? []);
            foreach ($types as $type) {
                $result[$subject][$type] = ($result[$subject][$type] ?? 0) + 1;
            }
        }

        return $result;
    }

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

    private function rangeMinutes(int $userId, string $from, string $to): int
    {
        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereBetween('daily_logs.date', [$from, $to])
            ->sum('study_sessions.minutes');
    }

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

    private function currentStreak(int $userId, Carbon $now): int
    {
        $streak  = 0;
        $current = $now->copy()->startOfDay();

        while (true) {
            $hasSession = DB::table('daily_logs')
                ->where('user_id', $userId)
                ->where('date', $current->toDateString())
                ->whereExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('study_sessions')
                    ->whereColumn('study_sessions.daily_log_id', 'daily_logs.id'))
                ->exists();

            if (!$hasSession) {
                break;
            }

            $streak++;
            $current->subDay();

            if ($streak >= 365) {
                break;
            }
        }

        return $streak;
    }

    private function lastStudiedSubject(int $userId): string
    {
        $result = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->orderByDesc('daily_logs.date')
            ->orderByDesc('study_sessions.id')
            ->value('subjects.name');

        return $result ?? '';
    }
}

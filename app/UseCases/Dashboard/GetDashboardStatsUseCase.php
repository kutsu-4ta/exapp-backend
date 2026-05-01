<?php

namespace App\UseCases\Dashboard;

use App\Models\DailyLog;
use App\Models\StudySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardStatsUseCase
{
    public function __invoke(int $userId): array
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $today = $now->toDateString();
        $sevenDaysAgo = $now->copy()->subDays(6)->toDateString();
        $thirtyDaysAgo = $now->copy()->subDays(29)->toDateString();

        return [
            'currentStreak' => $this->currentStreak($userId, $now),
            'allTotalMinutes' => $this->allTotalMinutes($userId),
            'allTotalDays' => $this->allTotalDays($userId),
            'thisMonthMinutes' => $this->thisMonthMinutes($userId, $year, $month),
            'thisMonthDays' => $this->thisMonthDays($userId, $year, $month),
            'thisWeekTotalMinutes' => $this->thisWeekTotalMinutes($userId), // 月曜始まりの一週間
            'last7DaysMinutes' => $this->last7DaysMinutes($userId, $sevenDaysAgo, $today),
            'weeklyAvgMinutes' => $this->weeklyAvgMinutes($this->thisMonthMinutes($userId, $year, $month), $now),
            'subjectMinutes' => $this->subjectMinutes($userId, $year, $month),
            'lastTouchedBySubject' => $this->lastTouchedBySubject($userId),
            'dailyMinutes' => $this->dailyMinutes($userId, $thirtyDaysAgo, $today),
        ];
    }

    private function allTotalMinutes(int $userId): int
    {
        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->sum('study_sessions.minutes');
    }

    private function allTotalDays(int $userId): int
    {
        return (int) DailyLog::where('user_id', $userId)
            ->whereHas('studySessions')
            ->count();
    }

    private function thisMonthMinutes(int $userId, int $year, int $month): int
    {
        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->sum('study_sessions.minutes');
    }

    private function thisMonthDays(int $userId, int $year, int $month): int
    {
        return (int) DailyLog::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereHas('studySessions')
            ->count();
    }

    // 連続学習日数
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

    private function thisWeekTotalMinutes(int $userId): int
    {
        // 今週の月曜日の日付を取得 (Carbonはデフォルトで月曜が週の開始)
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        // 今日の日付を取得
        $today = Carbon::now()->toDateString();

        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereBetween('daily_logs.date', [$startOfWeek, $today])
            ->sum('study_sessions.minutes');
    }

    private function last7DaysMinutes(int $userId, string $from, string $to): int
    {
        return (int) StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereBetween('daily_logs.date', [$from, $to])
            ->sum('study_sessions.minutes');
    }

    private function weeklyAvgMinutes(int $thisMonthMinutes, Carbon $now): float
    {
        $elapsedWeeks = $now->dayOfMonth / 7;

        if ($elapsedWeeks <= 0) {
            return 0.0;
        }

        return round($thisMonthMinutes / $elapsedWeeks, 1);
    }

    private function subjectMinutes(int $userId, int $year, int $month): array
    {
        return StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            // subjects テーブルを結合して名前を取得するように変更
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->groupBy('subjects.id', 'subjects.name') // subject_id または name でグループ化
            ->orderByDesc(DB::raw('SUM(study_sessions.minutes)'))
            ->get([
                'subjects.name as subject', // 名前をエイリアスで取得
                DB::raw('SUM(study_sessions.minutes) as minutes'),
            ])
            ->map(fn ($row) => [
                'subject' => $row->subject,
                'minutes' => (int) $row->minutes,
            ])
            ->toArray();
    }

    private function lastTouchedBySubject(int $userId): array
    {
        // subjects はマスターデータ（user_idなし）になったため、
        // where('s.user_id', $userId) を削除し、
        // 外部結合の条件の中で特定のユーザーのログに絞り込むように変更します
        return DB::table('subjects as s')
            ->leftJoin('study_sessions as ss', 's.id', '=', 'ss.subject_id')
            ->leftJoin('daily_logs as dl', function ($join) use ($userId) {
                $join->on('dl.id', '=', 'ss.daily_log_id')
                    ->where('dl.user_id', '=', $userId);
            })
            // subjectsにuser_idがないので、ここでは全科目（マスター）に対して
            // そのユーザーの最終学習日を紐付ける形になります
            ->groupBy('s.id', 's.name', 's.display_order')
            ->orderBy('s.display_order')
            ->get([
                's.name as subject',
                DB::raw('MAX(dl.date) as lastDate'),
            ])
            ->toArray();
    }

    private function dailyMinutes(int $userId, string $from, string $to): array
    {
        $rows = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereBetween('daily_logs.date', [$from, $to])
            ->groupBy('daily_logs.date')
            ->orderBy('daily_logs.date')
            ->get([
                'daily_logs.date as date',
                DB::raw('SUM(study_sessions.minutes) as minutes'),
            ])
            ->keyBy(fn ($r) => $r->date instanceof Carbon ? $r->date->toDateString() : (string) $r->date);

        $result = [];
        $current = Carbon::parse($from);
        $end = Carbon::parse($to);

        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $result[] = [
                'date' => $dateStr,
                'minutes' => isset($rows[$dateStr]) ? (int) $rows[$dateStr]->minutes : 0,
            ];
            $current->addDay();
        }

        return $result;
    }
}

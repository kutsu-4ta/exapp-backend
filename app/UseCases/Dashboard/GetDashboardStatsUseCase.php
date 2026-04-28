<?php

namespace App\UseCases\Dashboard;

use App\Models\DailyLog;
use App\Models\StudySession;
use App\Models\Subject;
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

        $thisMonthMinutes = $this->thisMonthMinutes($userId, $year, $month);
        $thisMonthDays = $this->thisMonthDays($userId, $year, $month);
        $last7DaysMinutes = $this->last7DaysMinutes($userId, $sevenDaysAgo, $today);
        $weeklyAvgMinutes = $this->weeklyAvgMinutes($thisMonthMinutes, $now);
        $subjectMinutes = $this->subjectMinutes($userId, $year, $month);
        $lastTouchedBySubject = $this->lastTouchedBySubject($userId);
        $dailyMinutes = $this->dailyMinutes($userId, $thirtyDaysAgo, $today);

        return [
            'thisMonthMinutes' => $thisMonthMinutes,
            'thisMonthDays' => $thisMonthDays,
            'last7DaysMinutes' => $last7DaysMinutes,
            'weeklyAvgMinutes' => $weeklyAvgMinutes,
            'subjectMinutes' => $subjectMinutes,
            'lastTouchedBySubject' => $lastTouchedBySubject,
            'dailyMinutes' => $dailyMinutes,
        ];
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
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc(DB::raw('SUM(study_sessions.minutes)'))
            ->get([
                'subjects.name as subject',
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
        $touched = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
            ->where('daily_logs.user_id', $userId)
            ->groupBy('subjects.id', 'subjects.name')
            ->get([
                'subjects.name as subject',
                DB::raw('MAX(daily_logs.date) as last_date'),
            ])
            ->keyBy('subject');

        $allSubjects = Subject::where('user_id', $userId)
            ->orderBy('display_order')
            ->pluck('name');

        return $allSubjects->map(fn (string $name) => [
            'subject' => $name,
            'lastDate' => isset($touched[$name]) ? $touched[$name]->last_date : null,
        ])->values()->toArray();
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

<?php

namespace App\UseCases\Subject;

use App\Models\Subject;
use App\Models\SubjectAlertSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetSubjectAlertStatusUseCase
{
    private const DEFAULT_TOUCH_ALERT_ENABLED    = true;
    private const DEFAULT_THRESHOLD_DAYS         = 7;
    private const DEFAULT_INCLUDE_UNTOUCHED      = false;
    private const DEFAULT_MINUTES_ALERT_ENABLED  = false;
    private const DEFAULT_MINUTES_THRESHOLD_DAYS = 7;
    private const DEFAULT_MINUTES_THRESHOLD      = 60;

    public function __invoke(int $userId): array
    {
        $subjects = Subject::where('user_id', $userId)->orderBy('display_order')->get();

        if ($subjects->isEmpty()) {
            return [];
        }

        $subjectIds = $subjects->pluck('id');

        // アラート設定を一括取得（未設定科目はデフォルト値で補完）
        $settingsMap = SubjectAlertSetting::where('user_id', $userId)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->keyBy('subject_id');

        // 科目ごとの minutesThresholdDays（デフォルト込み）
        $thresholdDaysMap = $subjects->mapWithKeys(fn ($s) => [
            $s->id => $settingsMap->has($s->id)
                ? $settingsMap[$s->id]->minutes_threshold_days
                : self::DEFAULT_MINUTES_THRESHOLD_DAYS,
        ]);

        $maxDays  = max((int) $thresholdDaysMap->max(), 1);
        $fromDate = Carbon::today()->subDays($maxDays)->toDateString();
        $today    = Carbon::today()->toDateString();

        $subjectIdArray = $subjectIds->toArray();

        // 最終学習日を科目ごとに取得（全期間・1クエリ）
        $lastDates = DB::table('study_sessions')
            ->join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereIn('study_sessions.subject_id', $subjectIdArray)
            ->groupBy('study_sessions.subject_id')
            ->select([
                'study_sessions.subject_id',
                DB::raw('MAX(daily_logs.date) as last_date'),
            ])
            ->get()
            ->keyBy('subject_id');

        // 直近 maxDays 日分の学習時間を科目×日付で取得（1クエリ）
        $minuteRows = DB::table('study_sessions')
            ->join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->whereIn('study_sessions.subject_id', $subjectIdArray)
            ->whereBetween('daily_logs.date', [$fromDate, $today])
            ->groupBy('study_sessions.subject_id', 'daily_logs.date')
            ->select([
                'study_sessions.subject_id',
                'daily_logs.date',
                DB::raw('SUM(study_sessions.minutes) as minutes'),
            ])
            ->get()
            ->groupBy('subject_id');

        return $subjects->map(function ($subject) use ($settingsMap, $thresholdDaysMap, $lastDates, $minuteRows) {
            $setting = $settingsMap->get($subject->id) ?? new SubjectAlertSetting([
                'touch_alert_enabled'    => self::DEFAULT_TOUCH_ALERT_ENABLED,
                'threshold_days'         => self::DEFAULT_THRESHOLD_DAYS,
                'include_untouched'      => self::DEFAULT_INCLUDE_UNTOUCHED,
                'minutes_alert_enabled'  => self::DEFAULT_MINUTES_ALERT_ENABLED,
                'minutes_threshold_days' => self::DEFAULT_MINUTES_THRESHOLD_DAYS,
                'minutes_threshold'      => self::DEFAULT_MINUTES_THRESHOLD,
            ]);

            $lastDateRow = $lastDates->get($subject->id);
            $lastDate    = $lastDateRow ? (string) $lastDateRow->last_date : null;

            // 科目固有の窓でフィルタして集計
            $windowFrom    = Carbon::today()->subDays($thresholdDaysMap[$subject->id])->toDateString();
            $recentMinutes = (int) $minuteRows->get($subject->id, collect())
                ->filter(fn ($row) => (string) $row->date >= $windowFrom)
                ->sum('minutes');

            return [
                'subject'       => $subject->name,
                'lastDate'      => $lastDate,
                'recentMinutes' => $recentMinutes,
                'settings'      => [
                    'touchAlertEnabled'    => $setting->touch_alert_enabled,
                    'thresholdDays'        => $setting->threshold_days,
                    'includeUntouched'     => $setting->include_untouched,
                    'minutesAlertEnabled'  => $setting->minutes_alert_enabled,
                    'minutesThresholdDays' => $setting->minutes_threshold_days,
                    'minutesThreshold'     => $setting->minutes_threshold,
                ],
            ];
        })->values()->toArray();
    }
}

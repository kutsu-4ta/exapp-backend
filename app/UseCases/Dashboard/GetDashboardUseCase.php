<?php

namespace App\UseCases\Dashboard;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Models\DailyLog;
use App\UseCases\Subject\GetSubjectAlertStatusUseCase;
use Illuminate\Support\Carbon;

class GetDashboardUseCase
{
    public function __construct(
        private readonly GetDashboardStatsUseCase $statsUseCase,
        private readonly GetSubjectAlertStatusUseCase $alertUseCase,
        private readonly DailyLogRepositoryInterface $dailyLogRepository,
    ) {}

    public function __invoke(int $userId): array
    {
        $now      = Carbon::now();
        $todayStr = ($now->hour < 4 ? $now->copy()->subDay() : $now)->toDateString();

        $stats      = ($this->statsUseCase)($userId);
        $alertItems = ($this->alertUseCase)($userId);
        $todayLog   = $this->dailyLogRepository->findByDateWithSessions($userId, $todayStr);
        $prevDayLog = $this->resolvePrevDayLog($userId, $now);

        return [
            'stats'      => $stats,
            'todayLog'   => $todayLog,
            'prevDayLog' => $prevDayLog,
            'alertItems' => $alertItems,
        ];
    }

    // 04:00以降かつ前日ログが未完了・学習記録あり の場合のみ返す
    private function resolvePrevDayLog(int $userId, Carbon $now): ?DailyLog
    {
        if ($now->hour < 4) {
            return null;
        }

        $prevDayStr = $now->copy()->subDay()->toDateString();
        $log        = $this->dailyLogRepository->findByDateWithSessions($userId, $prevDayStr);

        if ($log && !$log->is_completed && $log->studySessions->sum('minutes') > 0) {
            return $log;
        }

        return null;
    }
}

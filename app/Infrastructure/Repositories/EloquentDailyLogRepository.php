<?php

namespace App\Infrastructure\Repositories;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Models\DailyLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentDailyLogRepository implements DailyLogRepositoryInterface
{
    public function findByMonth(int $userId, int $year, int $month): Collection
    {
        return DailyLog::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->withSum('studySessions', 'minutes')
            ->withCount('studySessions')
            ->orderBy('date')
            ->get();
    }

    public function findByDate(int $userId, string $date): ?DailyLog
    {
        return DailyLog::where('user_id', $userId)->where('date', $date)->first();
    }

    public function findByDateWithSessions(int $userId, string $date): ?DailyLog
    {
        return DailyLog::where('user_id', $userId)
            ->where('date', $date)
            ->with(['studySessions.subject'])
            ->first();
    }

    public function existsByDate(int $userId, string $date): bool
    {
        return DailyLog::where('user_id', $userId)->where('date', $date)->exists();
    }

    public function create(int $userId, string $date): DailyLog
    {
        return DB::transaction(function () use ($userId, $date): DailyLog {
            if ($this->existsByDate($userId, $date)) {
                abort(409, '指定日のデイリーログは既に存在します。');
            }

            $log = DailyLog::create(['user_id' => $userId, 'date' => $date]);

            return $log->load('studySessions.subject');
        });
    }

    public function updateReflection(DailyLog $dailyLog, ?string $reflection): DailyLog
    {
        $dailyLog->update(['reflection' => $reflection]);

        return $dailyLog->load('studySessions.subject');
    }

    public function complete(DailyLog $dailyLog): DailyLog
    {
        $dailyLog->update(['is_completed' => true, 'completed_at' => now()]);

        return $dailyLog->load('studySessions.subject');
    }

    public function uncomplete(DailyLog $dailyLog): DailyLog
    {
        $dailyLog->update(['is_completed' => false, 'completed_at' => null]);

        return $dailyLog->load('studySessions.subject');
    }
}

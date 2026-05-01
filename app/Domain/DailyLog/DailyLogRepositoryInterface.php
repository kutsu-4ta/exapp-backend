<?php

namespace App\Domain\DailyLog;

use App\Models\DailyLog;
use Illuminate\Support\Collection;

interface DailyLogRepositoryInterface
{
    public function findByMonth(int $userId, int $year, int $month): Collection;

    public function findByDate(int $userId, string $date): ?DailyLog;

    public function findByDateWithSessions(int $userId, string $date): ?DailyLog;

    public function existsByDate(int $userId, string $date): bool;

    public function create(int $userId, string $date): DailyLog;

    public function updateReflection(DailyLog $dailyLog, ?string $reflection): DailyLog;

    public function complete(DailyLog $dailyLog): DailyLog;

    public function uncomplete(DailyLog $dailyLog): DailyLog;

    public function delete(DailyLog $dailyLog): void;
}

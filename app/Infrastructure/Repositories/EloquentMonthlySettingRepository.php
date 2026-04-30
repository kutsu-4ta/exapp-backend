<?php

namespace App\Infrastructure\Repositories;

use App\Domain\MonthlySetting\MonthlySettingRepositoryInterface;
use App\Models\MonthlySetting;

class EloquentMonthlySettingRepository implements MonthlySettingRepositoryInterface
{
    public function findByYearMonth(int $userId, int $year, int $month): ?MonthlySetting
    {
        return MonthlySetting::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function upsert(int $userId, int $year, int $month, array $data): MonthlySetting
    {
        $setting = MonthlySetting::updateOrCreate(
            ['user_id' => $userId, 'year' => $year, 'month' => $month],
            $data,
        );

        return $setting->fresh();
    }
}

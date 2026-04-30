<?php

namespace App\Domain\MonthlySetting;

use App\Models\MonthlySetting;

interface MonthlySettingRepositoryInterface
{
    public function findByYearMonth(int $userId, int $year, int $month): ?MonthlySetting;

    public function upsert(int $userId, int $year, int $month, array $data): MonthlySetting;
}

<?php

namespace App\Services\AiAdvice;

final class AdviceContext
{
    public function __construct(
        public readonly int    $year,
        public readonly int    $month,
        public readonly int    $totalMonthMinutes,
        public readonly int    $studyDays,
        public readonly int    $last7DaysMinutes,
        public readonly int    $thisWeekMinutes,
        public readonly int    $currentStreak,
        /** @var array<string, int> 科目名 => 今月の学習分数 */
        public readonly array  $subjectMinutes,
        /** @var array<string, int> 科目名 => 苦手問題件数 */
        public readonly array  $weakSubjects,
        public readonly string $lastSubject,
        public readonly UserProfile $profile,
    ) {}

    public function weeklyAchievementRate(): float
    {
        if ($this->profile->weeklyTargetMinutes === 0) {
            return 0.0;
        }
        return round($this->thisWeekMinutes / $this->profile->weeklyTargetMinutes * 100, 1);
    }

    public function weeklyRemainingMinutes(): int
    {
        return max(0, $this->profile->weeklyTargetMinutes - $this->thisWeekMinutes);
    }
}

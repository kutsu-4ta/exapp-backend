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
        /** @var array<string, int> 科目名 => 累計学習分数 */
        public readonly array  $allTimeSubjectMinutes,
        /** @var array<string, int> 科目名 => 苦手問題件数 */
        public readonly array  $weakSubjects,
        /** @var string[] 直近30日間手付かずの科目名リスト */
        public readonly array  $untouchedSubjects,
        /** @var array<string, array<string, int>> 科目名 => [ミス種別 => 件数] */
        public readonly array  $failureTypesBySubject,
        public readonly string $lastSubject,
        public readonly UserProfile $profile,
        /** @var string[] ユーザー登録済み教材名リスト */
        public readonly array  $materials = [],
    ) {}

    public function weeklyRemainingMinutes(): int
    {
        return max(0, $this->profile->weeklyTargetMinutes - $this->thisWeekMinutes);
    }
}

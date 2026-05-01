<?php

namespace App\Services\AiAdvice;

/**
 * ユーザーのプロフィール情報。
 * 現在はデフォルト値で固定。将来的には user_profiles テーブルから取得する。
 */
final class UserProfile
{
    public function __construct(
        public readonly string $occupation,
        public readonly string $goal,
        public readonly string $weakAreas,
        public readonly string $strongAreas,
        public readonly string $interests,
        public readonly int    $weeklyTargetMinutes,
    ) {}

    public static function default(): self
    {
        return new self(
            occupation:           'ITエンジニア（SE）',
            goal:                 '中小企業診断士試験に一発合格（週45時間学習）',
            weakAreas:            '経営法務、中小企業経営・政策',
            strongAreas:          '財務・会計、経営情報システム',
            interests:            '物理学、歴史、哲学、言語学',
            weeklyTargetMinutes:  2700, // 45h × 60min
        );
    }
}

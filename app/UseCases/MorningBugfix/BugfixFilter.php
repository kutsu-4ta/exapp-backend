<?php

namespace App\UseCases\MorningBugfix;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use Illuminate\Support\Carbon;

class BugfixFilter
{
    public const DEFAULT_LIMIT = 5;
    public const MAX_LIMIT     = 10;

    public function __construct(
        public readonly array   $failureTypes,   // null 指定なし
        public readonly array   $subCategoryIds, // null 指定なし
        public readonly ?string $touchedOrder,  // 'recent'|'old'|null（random）
        public readonly int     $limit,
        public readonly array   $proficiencies, // Proficiency values の配列。空 = フィルターなし
        public readonly bool    $morningMode,   // true = Morning Bugfix 3段階フォールバック
        public readonly ?Carbon $date,          // morningMode 専用テスト用基準日
        public readonly ?string $subject = null, // 科目名フィルタ。null = 全科目
        public readonly array   $ranks = [],    // Rank values の配列。空 = フィルターなし
    ) {}

    /** Morning Bugfix デフォルト設定（パラメータ未指定時） */
    public static function morningDefault(?Carbon $date = null): self
    {
        return new self(
            failureTypes:   [],
            subCategoryIds: [],
            touchedOrder:  null,
            limit:         self::DEFAULT_LIMIT,
            proficiencies: [],
            morningMode:   true,
            date:          $date,
        );
    }

    /** Flash Bugfix デフォルト proficiency（△/× のみ） */
    public static function defaultProficiencies(): array
    {
        return [Proficiency::Partial->value, Proficiency::Incorrect->value];
    }
}

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
        public readonly ?string $failureType,   // null = フィルターなし
        public readonly ?int    $subCategoryId, // null = フィルターなし
        public readonly ?string $touchedOrder,  // 'recent'|'old'|null（random）
        public readonly int     $limit,
        public readonly array   $proficiencies, // Proficiency values の配列。空 = フィルターなし
        public readonly bool    $morningMode,   // true = Morning Bugfix 3段階フォールバック
        public readonly ?Carbon $date,          // morningMode 専用テスト用基準日
    ) {}

    /** Morning Bugfix デフォルト設定（パラメータ未指定時） */
    public static function morningDefault(?Carbon $date = null): self
    {
        return new self(
            failureType:   FailureType::MissingDefinition->value,
            subCategoryId: null,
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

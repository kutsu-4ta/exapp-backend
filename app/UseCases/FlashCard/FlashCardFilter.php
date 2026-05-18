<?php

namespace App\UseCases\FlashCard;

use App\Enums\Proficiency;

class FlashCardFilter
{
    public const DEFAULT_LIMIT = 5;
    public const MAX_LIMIT     = 10;

    public function __construct(
        public readonly string  $subject,
        public readonly array   $failureTypes,
        public readonly array   $subCategoryIds,
        public readonly ?string $touchedOrder,
        public readonly int     $limit,
        public readonly array   $proficiencies,
        public readonly bool    $formulaOnly,
        public readonly array   $ranks = [],    // Rank values の配列。空 = フィルターなし
    ) {}

    public static function defaultProficiencies(): array
    {
        return [Proficiency::Partial->value, Proficiency::Incorrect->value];
    }
}

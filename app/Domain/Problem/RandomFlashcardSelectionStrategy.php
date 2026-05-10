<?php

namespace App\Domain\Problem;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RandomFlashcardSelectionStrategy implements FlashcardSelectionStrategyInterface
{
    public function select(Builder $query, int $count): Collection
    {
        return $query->inRandomOrder()->limit($count)->get();
    }
}

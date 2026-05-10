<?php

namespace App\Domain\Problem;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface FlashcardSelectionStrategyInterface
{
    public function select(Builder $query, int $count): Collection;
}

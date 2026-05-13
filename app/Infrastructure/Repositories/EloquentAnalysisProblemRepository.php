<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Analysis\AnalysisProblemRepositoryInterface;
use App\Models\Problem;

class EloquentAnalysisProblemRepository implements AnalysisProblemRepositoryInterface
{
    public function updateNote(Problem $problem, string $note): Problem
    {
        $problem->update([
            'note' => $note,
        ]);

        return $problem->refresh();
    }
}

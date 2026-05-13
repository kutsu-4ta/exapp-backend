<?php

namespace App\Domain\Analysis;

use App\Models\Problem;

interface AnalysisProblemRepositoryInterface
{
    public function updateNote(Problem $problem, string $note): Problem;
}

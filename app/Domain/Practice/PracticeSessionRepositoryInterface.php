<?php

namespace App\Domain\Practice;

use App\Models\PracticeSession;

interface PracticeSessionRepositoryInterface
{
    public function create(
        int    $userId,
        int    $subjectId,
        string $date,
        int    $totalElapsedMs,
        int    $totalMinutes,
    ): PracticeSession;
}

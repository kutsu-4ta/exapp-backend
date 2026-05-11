<?php

namespace App\UseCases\Problem;

use App\Models\Problem;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TouchProblemUseCase
{
    public function __invoke(int $userId, int $problemId): Problem
    {
        $problem = Problem::where('user_id', $userId)->find($problemId);

        if ($problem === null) {
            throw new ModelNotFoundException("Problem {$problemId} not found.");
        }

        $problem->last_touched_at = Carbon::today();
        $problem->save();

        return $problem;
    }
}

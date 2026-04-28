<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;

class EloquentProblemRepository implements ProblemRepositoryInterface
{
    public function findAllByUser(int $userId): Collection
    {
        return Problem::with('subject')
            ->where('user_id', $userId)
            ->orderByDesc('solved_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?Problem
    {
        return Problem::with('subject')->where('user_id', $userId)->find($id);
    }

    public function create(int $userId, array $data): Problem
    {
        $problem = Problem::create(array_merge(['user_id' => $userId], $data));

        return $problem->load('subject');
    }

    public function update(Problem $problem, array $data): Problem
    {
        $problem->update($data);

        return $problem->load('subject');
    }

    public function delete(Problem $problem): void
    {
        $problem->delete();
    }
}

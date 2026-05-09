<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;

class EloquentProblemRepository implements ProblemRepositoryInterface
{
    public function findAllByUser(int $userId, ?int $limit = null): Collection
    {
        $query = Problem::where('user_id', $userId)
            ->orderByDesc('solved_at')
            ->orderByDesc('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?Problem
    {
        return Problem::where('user_id', $userId)->find($id);
    }

    public function create(int $userId, array $data): Problem
    {
        return Problem::create(array_merge(['user_id' => $userId], $data));
    }

    public function update(Problem $problem, array $data): Problem
    {
        $problem->update($data);

        return $problem->fresh();
    }

    public function delete(Problem $problem): void
    {
        $problem->delete();
    }
}

<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;

class EloquentProblemRepository implements ProblemRepositoryInterface
{
    public function findAllByUser(int $userId, ?int $limit = null, ?int $afterId = null): Collection
    {
        $query = Problem::where('user_id', $userId)
            ->orderByDesc('solved_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($afterId !== null) {
            $cursor = Problem::where('user_id', $userId)->find($afterId);
            if ($cursor) {
                $solvedAt  = $cursor->solved_at;
                $createdAt = $cursor->created_at;
                $cursorId  = $cursor->id;
                $query->where(function ($q) use ($solvedAt, $createdAt, $cursorId) {
                    $q->where('solved_at', '<', $solvedAt)
                      ->orWhere(function ($q2) use ($solvedAt, $createdAt, $cursorId) {
                          $q2->where('solved_at', $solvedAt)
                             ->where(function ($q3) use ($createdAt, $cursorId) {
                                 $q3->where('created_at', '<', $createdAt)
                                    ->orWhere(function ($q4) use ($createdAt, $cursorId) {
                                        $q4->where('created_at', $createdAt)
                                           ->where('id', '<', $cursorId);
                                    });
                             });
                      });
                });
            }
        }

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

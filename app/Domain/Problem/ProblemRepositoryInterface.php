<?php

namespace App\Domain\Problem;

use App\Models\Problem;
use Illuminate\Support\Collection;

interface ProblemRepositoryInterface
{
    public function findAllByUser(int $userId, ?int $limit = null, ?int $afterId = null): Collection;

    public function findByIdAndUser(int $id, int $userId): ?Problem;

    public function create(int $userId, array $data): Problem;

    public function update(Problem $problem, array $data): Problem;

    public function delete(Problem $problem): void;
}

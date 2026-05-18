<?php

namespace App\Domain\Sprint;

use App\Models\Sprint;
use Illuminate\Support\Collection;

interface SprintRepositoryInterface
{
    public function findAllByUser(int $userId): Collection;

    public function findByIdAndUser(int $id, int $userId): ?Sprint;

    public function findOrCreateBacklog(int $userId): Sprint;

    public function create(int $userId, array $data): Sprint;

    public function update(Sprint $sprint, array $data): Sprint;

    public function complete(Sprint $sprint, string $retrospective): Sprint;

    public function delete(Sprint $sprint): void;
}

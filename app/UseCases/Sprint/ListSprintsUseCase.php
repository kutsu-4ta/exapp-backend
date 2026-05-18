<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use Illuminate\Support\Collection;

class ListSprintsUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId): Collection
    {
        // バックログが存在しない場合は自動生成する
        $this->repository->findOrCreateBacklog($userId);

        return $this->repository->findAllByUser($userId);
    }
}

<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Models\Sprint;

class GetSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): ?Sprint
    {
        return $this->repository->findByIdAndUser($id, $userId);
    }
}

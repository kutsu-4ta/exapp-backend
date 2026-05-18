<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Models\Sprint;

class CreateSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, array $data): Sprint
    {
        return $this->repository->create($userId, $data);
    }
}

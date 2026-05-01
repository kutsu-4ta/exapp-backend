<?php

namespace App\UseCases\Material;

use App\Domain\Material\MaterialRepositoryInterface;
use Illuminate\Support\Collection;

class ListMaterialUseCase
{
    public function __construct(
        private readonly MaterialRepositoryInterface $repository
    ) {}

    public function __invoke(int $userId): Collection
    {
        return $this->repository->findAll($userId);
    }
}

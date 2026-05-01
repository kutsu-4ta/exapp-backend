<?php

namespace App\UseCases\Material;

use App\Domain\Material\MaterialRepositoryInterface;

class DeleteMaterialUseCase
{
    public function __construct(
        private readonly MaterialRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $name): void
    {
        $material = $this->repository->findByName($userId, $name);

        if ($material === null) {
            abort(404);
        }

        $this->repository->delete($material);
    }
}

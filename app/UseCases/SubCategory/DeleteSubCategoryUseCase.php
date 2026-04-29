<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;

class DeleteSubCategoryUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $id): void
    {
        $subCategory = $this->repository->findByIdAndUser($id, $userId);

        if ($subCategory === null) {
            abort(404);
        }

        $this->repository->delete($subCategory);
    }
}

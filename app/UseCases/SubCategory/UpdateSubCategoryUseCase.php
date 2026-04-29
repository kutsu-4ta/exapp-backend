<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Models\SubCategory;

class UpdateSubCategoryUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $id, array $data): SubCategory
    {
        $subCategory = $this->repository->findByIdAndUser($id, $userId);

        if ($subCategory === null) {
            abort(404);
        }

        return $this->repository->update($subCategory, $data);
    }
}

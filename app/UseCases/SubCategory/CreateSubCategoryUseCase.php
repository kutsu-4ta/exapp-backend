<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Models\SubCategory;

class CreateSubCategoryUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, array $data): SubCategory
    {
        return $this->repository->create($userId, $data);
    }
}

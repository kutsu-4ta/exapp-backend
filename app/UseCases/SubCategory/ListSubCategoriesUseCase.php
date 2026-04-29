<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use Illuminate\Support\Collection;

class ListSubCategoriesUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, ?string $subject = null): Collection
    {
        return $this->repository->findAllByUser($userId, $subject);
    }
}

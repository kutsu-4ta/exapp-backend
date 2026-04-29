<?php

namespace App\Domain\SubCategory;

use App\Models\SubCategory;
use Illuminate\Support\Collection;

interface SubCategoryRepositoryInterface
{
    public function findAllByUser(int $userId, ?string $subject = null): Collection;

    public function findByIdAndUser(int $id, int $userId): ?SubCategory;

    public function create(int $userId, array $data): SubCategory;

    public function update(SubCategory $subCategory, array $data): SubCategory;

    public function delete(SubCategory $subCategory): void;
}

<?php

namespace App\Domain\Material;

use App\Models\Material;
use Illuminate\Support\Collection;

interface MaterialRepositoryInterface
{
    public function findAll(int $userId): Collection;

    public function findByName(int $userId, string $name): ?Material;

    public function firstOrCreate(int $userId, string $name): Material;

    public function rename(Material $material, string $newName): Material;

    public function delete(Material $material): void;

    public function seedDefaults(int $userId): void;
}

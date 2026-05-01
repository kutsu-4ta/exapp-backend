<?php

namespace App\Domain\Subject;

use App\Models\Subject;
use Illuminate\Support\Collection;

interface SubjectRepositoryInterface
{
    public function findAll(int $userId): Collection;

    public function findByName(int $userId, string $name): ?Subject;

    public function firstOrCreate(int $userId, string $name): Subject;

    public function rename(Subject $subject, string $newName): Subject;

    public function delete(Subject $subject): void;

    public function seedDefaults(int $userId): void;
}

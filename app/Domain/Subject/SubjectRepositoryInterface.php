<?php

namespace App\Domain\Subject;

use App\Models\Subject;
use Illuminate\Support\Collection;

interface SubjectRepositoryInterface
{
    public function findAllByUser(int $userId): Collection;

    public function findByIdAndUser(int $id, int $userId): ?Subject;

    public function existsByNameAndUser(string $name, int $userId, ?int $excludeId = null): bool;

    public function create(int $userId, string $name, int $displayOrder): Subject;

    public function update(Subject $subject, string $name, int $displayOrder): Subject;

    public function delete(Subject $subject): void;

    public function isInUse(Subject $subject): bool;

    /** 新規ユーザー登録時にデフォルト科目を一括作成 */
    public function seedDefaults(int $userId): void;
}

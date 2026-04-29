<?php

namespace App\Domain\Subject;

use App\Models\Subject;
use Illuminate\Support\Collection;

interface SubjectRepositoryInterface
{
    /** 全科目の取得（display_order順） */
    public function findAll(): Collection;

    /** IDによる取得 */
    public function findById(int $id): ?Subject;

    /** 名前による取得（Requestからの変換用） */
    public function findByName(string $name): ?Subject;

    /** 新規ユーザーへのデフォルトデータ（SubCategoryなど）の投入 */
    public function seedDefaults(int $userId): void;
}

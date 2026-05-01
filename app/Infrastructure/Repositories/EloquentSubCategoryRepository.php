<?php

namespace App\Infrastructure\Repositories;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Models\SubCategory;
use Illuminate\Support\Collection;

class EloquentSubCategoryRepository implements SubCategoryRepositoryInterface
{
    public function findAllByUser(int $userId, ?string $subject = null): Collection
    {
        $query = SubCategory::query()
            // subjectsテーブルを結合して表示順と名前を利用可能にする
            ->join('subjects', 'subjects.id', '=', 'sub_categories.subject_id')
            ->where('sub_categories.user_id', $userId)
            // 以前の 'subject' ではなく subjects.display_order でソート
            ->orderBy('subjects.display_order')
            ->orderBy('sub_categories.name');

        if ($subject !== null) {
            // 文字列でフィルタリングが来る場合は subjects.name を見る
            $query->where('subjects.name', $subject);
        }

        // selectを指定して、名前衝突を防ぎつつ全カラム取得
        return $query->get(['sub_categories.*']);
    }

    public function findByIdAndUser(int $id, int $userId): ?SubCategory
    {
        return SubCategory::where('user_id', $userId)->find($id);
    }

    public function firstOrCreate(int $userId, int $subjectId, string $name): SubCategory
    {
        return SubCategory::firstOrCreate([
            'user_id'    => $userId,
            'subject_id' => $subjectId,
            'name'       => $name,
        ]);
    }

    public function create(int $userId, array $data): SubCategory
    {
        // 外部からのデータが 'subject_id' になっていることを想定
        return SubCategory::create(array_merge(['user_id' => $userId], $data));
    }

    public function update(SubCategory $subCategory, array $data): SubCategory
    {
        $subCategory->update($data);

        return $subCategory->fresh();
    }

    public function delete(SubCategory $subCategory): void
    {
        $subCategory->delete();
    }
}

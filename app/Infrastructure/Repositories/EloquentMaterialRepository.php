<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Models\Material;
use App\Models\StudySession; // 学習記録との紐付けがある場合
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentMaterialRepository implements MaterialRepositoryInterface
{
    public function findAll(int $userId): Collection
    {
        return Material::where('user_id', $userId)
            ->orderBy('display_order')
            ->get();
    }

    public function findByName(int $userId, string $name): ?Material
    {
        return Material::where('user_id', $userId)
            ->where('name', $name)
            ->first();
    }

    public function firstOrCreate(int $userId, string $name): Material
    {
        return Material::firstOrCreate(
            ['user_id' => $userId, 'name' => $name],
            ['display_order' => (Material::where('user_id', $userId)->max('display_order') ?? -1) + 1],
        );
    }

    public function rename(Material $material, string $newName): Material
    {
        $material->update(['name' => $newName]);
        return $material->fresh();
    }

    public function delete(Material $material): void
    {
        DB::transaction(function () use ($material) {
            // 教材に紐づく学習ログ等があればここでクリーンアップ
            // StudySession::where('material_id', $material->id)->update(['material_id' => null]);

            $material->delete();
        });
    }

    public function seedDefaults(int $userId): void
    {
        // 診断士試験でお馴染みの、主要な教材種別をデフォルトとして用意
        $defaults = [
            '過去問（1次）',
            '過去問（2次）',
            'テキスト・参考書',
            '問題集（スピード問題集等）',
            '模試',
            'スタディング / 診断士ゼミナール',
            'YouTube / その他',
        ];

        foreach ($defaults as $order => $name) {
            Material::firstOrCreate(
                ['user_id' => $userId, 'name' => $name],
                ['display_order' => $order],
            );
        }
    }
}
